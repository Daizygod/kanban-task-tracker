<?php

namespace App\Livewire\Boards;

use App\Enums\TaskType;
use App\Exceptions\StatusChangeBlockedException;
use App\Models\Board;
use App\Models\Project;
use App\Models\Task;
use App\Services\Centrifugo;
use App\Support\TaskFilter;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class ProjectBoard extends Component
{
    public Project $project;

    public string $tab = 'epics';

    /** Умный фильтр карточек: «поле: значение» + свободный текст (TaskFilter) */
    #[Url(as: 'q')]
    public string $filter = '';

    /** Выбранная доска; null — дефолтная («Все задачи») */
    #[Url(as: 'board')]
    public ?int $boardId = null;

    public function mount(Project $project, string $tab = 'epics'): void
    {
        abort_unless($project->hasMember(Auth::user()), 403);

        $this->project = $project;
        $this->tab = $tab;
    }

    /** Текущая доска: из URL или дефолтная */
    private function currentBoard(): Board
    {
        $board = $this->boardId
            ? $this->project->boards()->find($this->boardId)
            : null;

        return $board ?? $this->project->defaultBoard();
    }

    /** Слот шапки живёт вне DOM Livewire-компонента, поэтому глобальное событие */
    #[On('board-select')]
    public function selectBoard(int $boardId): void
    {
        $board = $this->project->boards()->findOrFail($boardId);

        // Дефолтная доска — чистый URL без параметра
        $this->boardId = $board->is_default ? null : $board->id;
    }

    /** Кто-то другой изменил доску — просто перерисовываемся */
    #[On('board-remote-update')]
    public function handleRemoteUpdate(): void {}

    #[On('board-filter')]
    public function setFilter(string $value = ''): void
    {
        $this->filter = $value;
    }

    /** Создание/изменение задачи в модалках этой же вкладки */
    #[On('task-saved')]
    public function handleTaskSaved(): void {}

    /** Доска при открытии модалки не меняется — не перерисовываем её */
    #[Renderless]
    public function openTask(int $taskId): void
    {
        $this->dispatch('open-task', taskId: $taskId);
    }

    /**
     * Дроп карточки: колонка задаёт статус, строка — родителя (эпик/историю).
     * $applyParent=false на плоской доске задач, где строк нет.
     */
    /**
     * Быстрое создание карточки плюсиком в ячейке: колонка задаёт статус,
     * строка — родителя. На вкладке «Эпики» карточки — истории, иначе — задачи.
     */
    public function quickCreate(int $statusId, ?int $parentId, string $title): void
    {
        $title = trim($title);

        if ($title === '') {
            return;
        }

        $status = $this->project->statuses()->findOrFail($statusId);
        $type = $this->tab === 'epics' ? TaskType::Story : TaskType::Task;

        $task = new Task([
            'project_id' => $this->project->id,
            'type' => $type,
            'parent_id' => $parentId ?: null,
            'status_id' => $status->id,
            'title' => $title,
            'priority_id' => $this->project->defaultPriority()->id,
            'created_by' => Auth::id(),
        ]);

        if ($task->parent_id) {
            $parent = $this->project->tasks()->find($task->parent_id);

            try {
                $task->validateParent($parent);
            } catch (InvalidArgumentException $e) {
                $this->dispatch('toast', message: $e->getMessage(), type: 'error');

                return;
            }
        }

        $task->save();

        app(Centrifugo::class)->publish(Centrifugo::boardChannel($this->project->id), [
            'event' => 'task-created',
            'taskId' => $task->id,
        ]);

        $this->dispatch('toast', message: "Создана {$task->full_number}: {$task->title}");
    }

    /** Перестановка карточек внутри одной ячейки — только порядок */
    public function reorderCell(array $orderedIds): void
    {
        $this->applyCellOrder($orderedIds);

        app(Centrifugo::class)->publish(Centrifugo::boardChannel($this->project->id), [
            'event' => 'task-moved',
            'movedBy' => Auth::id(),
        ]);
    }

    /** Сохраняет вертикальный порядок ячейки: board_order = позиция в списке */
    private function applyCellOrder(array $orderedIds): void
    {
        $ids = collect($orderedIds)->map(fn ($id) => (int) $id)->filter()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $ownIds = $this->project->tasks()->whereIn('tasks.id', $ids)->pluck('id')->all();

        foreach ($ids as $index => $id) {
            if (in_array($id, $ownIds, true)) {
                Task::whereKey($id)->update(['board_order' => $index + 1]);
            }
        }
    }

    public function moveCard(int $taskId, int $statusId, ?int $parentId = null, bool $applyParent = false, array $orderedIds = []): void
    {
        $task = $this->project->tasks()->findOrFail($taskId);
        $status = $this->project->statuses()->findOrFail($statusId);

        if ($applyParent) {
            $newParentId = $parentId ?: null;

            if ($newParentId !== $task->parent_id) {
                $parent = $newParentId ? $this->project->tasks()->find($newParentId) : null;

                try {
                    $task->validateParent($parent);
                } catch (InvalidArgumentException $e) {
                    $this->dispatch('toast', message: $e->getMessage(), type: 'error');

                    return;
                }

                $task->parent_id = $newParentId;
                $task->save();
            }
        }

        try {
            $task->moveToStatus($status, Auth::user());
        } catch (StatusChangeBlockedException $e) {
            $blockers = $e->blockers->map(fn ($t) => $t->full_number)->implode(', ');
            $this->dispatch('toast', message: "Нельзя завершить {$task->full_number}: блокируется задачами {$blockers}.", type: 'error');

            return;
        }

        $this->applyCellOrder($orderedIds);

        app(Centrifugo::class)->publish(Centrifugo::boardChannel($this->project->id), [
            'event' => 'task-moved',
            'taskId' => $task->id,
            'statusId' => $status->id,
            'movedBy' => Auth::id(),
        ]);
    }

    /**
     * Строки доски: для вкладок «Эпики»/«Истории» — свимлейны по родителю
     * (+ строка «Без эпика/Без истории» в конце), для «Задач» — одна строка.
     *
     * @return array<int, array{key: string, header: ?\App\Models\Task, cards: \Illuminate\Support\Collection}>
     */
    private function buildRows(): array
    {
        $cardType = match ($this->tab) {
            'epics' => TaskType::Story,
            default => TaskType::Task,
        };

        $board = $this->currentBoard();

        $cards = $this->project->tasks()
            ->where('type', $cardType)
            // Пользовательская доска показывает только включённые на ней задачи
            ->when(! $board->is_default, fn ($query) => $query
                ->whereHas('boards', fn ($q) => $q->whereKey($board->id)))
            ->tap(fn ($query) => TaskFilter::apply($query, $this->project, $this->filter))
            ->with(['assignee', 'status', 'priority', 'dependsOn.status'])
            ->withCount('children')
            // Порядок в ячейке ручной (drag&drop), новые карточки — в конец
            ->orderBy('tasks.board_order')
            ->orderBy('tasks.number')
            ->get();

        if ($this->tab === 'tasks') {
            return [[
                'key' => 'all',
                'header' => null,
                'cards' => $cards->groupBy('status_id'),
            ]];
        }

        $parentType = $this->tab === 'epics' ? TaskType::Epic : TaskType::Story;

        $parents = $this->project->tasks()
            ->where('type', $parentType)
            ->with('status')
            ->orderBy('number')
            ->get();

        $byParent = $cards->groupBy(fn ($card) => $card->parent_id ?? 0);

        $rows = $parents->map(fn ($parent) => [
            'key' => "parent-{$parent->id}",
            'header' => $parent,
            'cards' => ($byParent[$parent->id] ?? collect())->groupBy('status_id'),
        ])->all();

        // «Без эпика» / «Без истории» — всегда в конце списка строк
        $rows[] = [
            'key' => 'orphans',
            'header' => null,
            'cards' => ($byParent[0] ?? collect())->groupBy('status_id'),
        ];

        return $rows;
    }

    public function render()
    {
        $rows = $this->buildRows();
        $statuses = $this->project->statuses;

        $statusCounts = [];
        $totalCards = 0;

        foreach ($statuses as $status) {
            $count = 0;

            foreach ($rows as $row) {
                $count += ($row['cards'][$status->id] ?? collect())->count();
            }

            $statusCounts[$status->id] = $count;
            $totalCards += $count;
        }

        return view('livewire.boards.project-board', [
            'statuses' => $statuses,
            'rows' => $rows,
            'statusCounts' => $statusCounts,
            'totalCards' => $totalCards,
            'boards' => $this->project->boards,
            'currentBoard' => $this->currentBoard(),
            'filterMeta' => TaskFilter::meta($this->project),
            'quickCardLabel' => $this->tab === 'epics' ? 'Новая история' : 'Новая задача',
            // стартовая раскладка до инициализации Alpine (коллапс — на клиенте)
            'gridTemplate' => $statuses->map(fn () => 'minmax(245px, 1fr)')->implode(' '),
            'orphanRowTitle' => $this->tab === 'epics' ? 'Без эпика' : 'Без истории',
        ])->title("{$this->project->name} — доска");
    }
}
