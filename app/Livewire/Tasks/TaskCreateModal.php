<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Task;
use App\Services\Centrifugo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskCreateModal extends Component
{
    public Project $project;

    public bool $show = false;

    public string $type = 'task';

    public string $title = '';

    public string $description = '';

    public ?int $parentId = null;

    public string $priority = 'normal';

    public ?int $assigneeId = null;

    public ?string $dueDate = null;

    #[On('open-create-task')]
    public function open(string $type = 'task'): void
    {
        $this->reset('title', 'description', 'parentId', 'assigneeId', 'dueDate');
        $this->resetValidation();
        $this->priority = 'normal';
        $this->type = in_array($type, ['epic', 'story', 'task'], true) ? $type : 'task';
        $this->show = true;
    }

    public function updatedType(): void
    {
        $this->parentId = null;
    }

    public function create(): void
    {
        $validated = $this->validate([
            'type' => ['required', Rule::in(['epic', 'story', 'task'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'parentId' => ['nullable', 'integer'],
            'priority' => ['required', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'assigneeId' => ['nullable', Rule::exists('project_user', 'user_id')->where('project_id', $this->project->id)],
            'dueDate' => ['nullable', 'date'],
        ], attributes: [
            'title' => 'название',
            'dueDate' => 'срок',
        ]);

        $task = new Task([
            'project_id' => $this->project->id,
            'type' => $validated['type'],
            'parent_id' => $validated['parentId'] ?: null,
            'status_id' => $this->project->statuses->first()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?: null,
            'priority' => $validated['priority'],
            'created_by' => Auth::id(),
            'assignee_id' => $validated['assigneeId'] ?: null,
            'due_date' => $validated['dueDate'] ?: null,
        ]);

        if ($task->parent_id) {
            $parent = $this->project->tasks()->find($task->parent_id);

            try {
                $task->validateParent($parent);
            } catch (InvalidArgumentException $e) {
                $this->addError('parentId', $e->getMessage());

                return;
            }
        }

        $task->save();

        app(Centrifugo::class)->publish(Centrifugo::boardChannel($this->project->id), [
            'event' => 'task-created',
            'taskId' => $task->id,
        ]);

        $this->show = false;
        $this->dispatch('task-saved');
        $this->dispatch('toast', message: "Создана {$task->full_number}: {$task->title}");
    }

    public function render()
    {
        $parentOptions = collect();

        $parentType = TaskType::from($this->type)->allowedParentType();

        if ($parentType !== null) {
            $parentOptions = $this->project->tasks()
                ->where('type', $parentType)
                ->orderBy('number')
                ->get(['id', 'number', 'title', 'project_id']);
        }

        return view('livewire.tasks.task-create-modal', [
            'parentOptions' => $parentOptions,
            'members' => $this->project->members()->orderBy('name')->get(),
            'priorities' => TaskPriority::cases(),
            'parentLabel' => $parentType === TaskType::Epic ? 'Эпик' : 'История',
        ]);
    }
}
