<?php

namespace App\Livewire\Tasks;

use App\Models\Project;
use App\Support\TaskFilter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Список всех задач проекта: бесконечная таблица + умный фильтр */
#[Layout('layouts.app')]
class TaskList extends Component
{
    private const PAGE_SIZE = 50;

    public Project $project;

    #[Url(as: 'q')]
    public string $filter = '';

    public int $limit = self::PAGE_SIZE;

    public function mount(Project $project): void
    {
        abort_unless($project->hasMember(Auth::user()), 403);

        $this->project = $project;
    }

    /** Поле фильтра живёт в слоте шапки — значение приходит глобальным событием */
    #[On('list-filter')]
    public function setFilter(string $value = ''): void
    {
        $this->filter = $value;
        $this->limit = self::PAGE_SIZE;
    }

    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    /** Правки в модалке задачи — перерисовать строки */
    #[On('task-saved')]
    public function handleTaskSaved(): void {}

    public function render()
    {
        $query = $this->project->tasks()
            ->with(['status', 'priority', 'assignee', 'creator', 'parent'])
            ->tap(fn ($q) => TaskFilter::apply($q, $this->project, $this->filter))
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        $total = (clone $query)->count();
        $tasks = $query->limit($this->limit)->get();

        return view('livewire.tasks.task-list', [
            'tasks' => $tasks,
            'total' => $total,
            'hasMore' => $tasks->count() < $total,
            'filterMeta' => TaskFilter::meta($this->project),
        ])->title("{$this->project->name} — задачи");
    }
}
