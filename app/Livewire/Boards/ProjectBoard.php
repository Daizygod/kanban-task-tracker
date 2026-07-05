<?php

namespace App\Livewire\Boards;

use App\Enums\TaskType;
use App\Exceptions\StatusChangeBlockedException;
use App\Models\Project;
use App\Services\Centrifugo;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

#[Layout('layouts.app')]
class ProjectBoard extends Component
{
    public Project $project;

    public string $tab = 'epics';

    /** Поиск по номеру/названию — «Фильтр карточек на доске» */
    public string $filter = '';


    public function mount(Project $project, string $tab = 'epics'): void
    {
        abort_unless($project->hasMember(Auth::user()), 403);

        $this->project = $project;
        $this->tab = $tab;
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
    public function moveCard(int $taskId, int $statusId, ?int $parentId = null, bool $applyParent = false): void
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

        $cards = $this->project->tasks()
            ->where('type', $cardType)
            ->when(trim($this->filter) !== '', function ($query) {
                $needle = trim($this->filter);

                $query->where(fn ($q) => $q
                    ->where('title', 'ilike', "%{$needle}%")
                    // key провалидирован как [A-Z]{3}, интерполяция безопасна
                    ->orWhereRaw("'{$this->project->key}-' || number ilike ?", ["%{$needle}%"]));
            })
            ->with(['assignee', 'status', 'priority', 'dependsOn.status'])
            ->withCount('children')
            // Важные приоритеты (больший order) — выше в колонке
            ->leftJoin('priorities', 'priorities.id', '=', 'tasks.priority_id')
            ->orderByDesc('priorities.order')
            ->orderBy('tasks.number')
            ->select('tasks.*')
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
            // стартовая раскладка до инициализации Alpine (коллапс — на клиенте)
            'gridTemplate' => $statuses->map(fn () => 'minmax(245px, 1fr)')->implode(' '),
            'orphanRowTitle' => $this->tab === 'epics' ? 'Без эпика' : 'Без истории',
        ])->title("{$this->project->name} — доска");
    }
}
