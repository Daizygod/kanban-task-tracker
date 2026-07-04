<?php

namespace App\Livewire\Boards;

use App\Enums\TaskType;
use App\Exceptions\StatusChangeBlockedException;
use App\Models\Project;
use App\Services\Centrifugo;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class ProjectBoard extends Component
{
    public Project $project;

    public string $tab = 'epics';

    public function mount(Project $project, string $tab = 'epics'): void
    {
        abort_unless($project->hasMember(Auth::user()), 403);

        $this->project = $project;
        $this->tab = $tab;
    }

    /** Кто-то другой изменил доску — просто перерисовываемся */
    #[On('board-remote-update')]
    public function handleRemoteUpdate(): void {}

    /** Создание/изменение задачи в модалках этой же вкладки */
    #[On('task-saved')]
    public function handleTaskSaved(): void {}

    public function openTask(int $taskId): void
    {
        $this->dispatch('open-task', taskId: $taskId);
    }

    public function moveCard(int $taskId, int $statusId): void
    {
        $task = $this->project->tasks()->findOrFail($taskId);
        $status = $this->project->statuses()->findOrFail($statusId);

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
            ->with(['assignee', 'status', 'dependsOn.status'])
            ->withCount('children')
            ->orderByRaw("
                case priority
                    when 'show-stopper' then 5
                    when 'critical' then 4
                    when 'major' then 3
                    when 'normal' then 2
                    else 1
                end desc
            ")
            ->orderBy('number')
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
        return view('livewire.boards.project-board', [
            'statuses' => $this->project->statuses,
            'rows' => $this->buildRows(),
            'orphanRowTitle' => $this->tab === 'epics' ? 'Без эпика' : 'Без истории',
        ])->title("{$this->project->name} — доска");
    }
}
