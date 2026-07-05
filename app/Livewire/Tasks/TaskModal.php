<?php

namespace App\Livewire\Tasks;

use App\Exceptions\StatusChangeBlockedException;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use App\Services\Centrifugo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskModal extends Component
{
    public Project $project;

    public ?int $taskId = null;

    public bool $show = false;

    // Редактирование
    public string $titleDraft = '';

    public string $descriptionDraft = '';

    public bool $editingDescription = false;

    // Комментарий
    public string $commentBody = '';

    // Учёт времени
    public string $timeValue = '';

    public string $timeUnit = 'hours';

    public string $timeDate = '';

    public string $timeDescription = '';

    // Зависимости
    public string $depQuery = '';

    // Фильтры ленты активности
    public bool $showComments = true;

    public bool $showHistory = true;

    #[On('open-task')]
    public function open(int $taskId): void
    {
        $task = $this->project->tasks()->find($taskId);

        if (! $task) {
            return;
        }

        $this->reset('commentBody', 'timeValue', 'timeDescription', 'depQuery', 'editingDescription');
        $this->resetValidation();

        $this->taskId = $task->id;
        $this->titleDraft = $task->title;
        $this->descriptionDraft = (string) $task->description;
        $this->timeDate = now()->toDateString();
        $this->timeUnit = 'hours';
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->taskId = null;
    }

    private function task(): Task
    {
        return $this->project->tasks()
            ->with(['status', 'priority', 'assignee', 'creator', 'parent', 'children.status', 'dependsOn.status', 'dependents.status'])
            ->findOrFail($this->taskId);
    }

    private function publish(string $event): void
    {
        app(Centrifugo::class)->publish(Centrifugo::boardChannel($this->project->id), [
            'event' => $event,
            'taskId' => $this->taskId,
        ]);
    }

    // ------------------------------------------------------------- Метаданные

    public function setStatus(int $statusId): void
    {
        $status = $this->project->statuses()->findOrFail($statusId);

        try {
            $this->task()->moveToStatus($status, Auth::user());
        } catch (StatusChangeBlockedException $e) {
            $blockers = $e->blockers->map(fn ($t) => $t->full_number)->implode(', ');
            $this->dispatch('toast', message: "Нельзя завершить: блокируется задачами {$blockers}.", type: 'error');

            return;
        }

        $this->publish('task-moved');
        $this->dispatch('task-saved');
    }

    public function setAssignee(?int $userId): void
    {
        $this->task()->update(['assignee_id' => $userId ?: null]);
        $this->publish('task-updated');
        $this->dispatch('task-saved');
    }

    public function setPriority(int $priorityId): void
    {
        $priority = $this->project->priorities()->findOrFail($priorityId);

        $this->task()->update(['priority_id' => $priority->id]);
        $this->publish('task-updated');
        $this->dispatch('task-saved');
    }

    public function setDueDate(?string $date): void
    {
        $this->task()->update(['due_date' => $date ?: null]);
        $this->publish('task-updated');
        $this->dispatch('task-saved');
    }

    public function saveTitle(): void
    {
        $title = trim($this->titleDraft);

        if ($title === '') {
            $this->titleDraft = $this->task()->title;

            return;
        }

        $this->task()->update(['title' => $title]);
        $this->publish('task-updated');
        $this->dispatch('task-saved');
    }

    public function saveDescription(): void
    {
        $this->task()->update(['description' => trim($this->descriptionDraft) ?: null]);
        $this->editingDescription = false;
    }

    // ------------------------------------------------------------ Комментарии

    public function addComment(): void
    {
        $this->validate(
            ['commentBody' => ['required', 'string', 'max:10000']],
            ['commentBody.required' => 'Комментарий не может быть пустым.'],
        );

        $this->task()->comments()->create([
            'user_id' => Auth::id(),
            'body' => trim($this->commentBody),
        ]);

        $this->commentBody = '';
    }

    // ----------------------------------------------------------- Учёт времени

    public function addTimeLog(): void
    {
        $this->validate([
            'timeValue' => ['required', 'numeric', 'gt:0', 'max:10000'],
            'timeUnit' => ['required', Rule::in(['hours', 'minutes'])],
            'timeDate' => ['required', 'date'],
            'timeDescription' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['timeValue' => 'время', 'timeDate' => 'дата']);

        $minutes = (int) round(
            $this->timeUnit === 'hours' ? ((float) $this->timeValue) * 60 : (float) $this->timeValue
        );

        if ($minutes < 1) {
            $this->addError('timeValue', 'Слишком маленький интервал.');

            return;
        }

        $this->task()->timeLogs()->create([
            'user_id' => Auth::id(),
            'minutes' => $minutes,
            'description' => trim($this->timeDescription) ?: null,
            'logged_date' => $this->timeDate,
        ]);

        $this->reset('timeValue', 'timeDescription');
        $this->dispatch('toast', message: 'Время записано.');
    }

    public function deleteTimeLog(int $timeLogId): void
    {
        TimeLog::where('id', $timeLogId)
            ->where('task_id', $this->taskId)
            ->where('user_id', Auth::id())
            ->delete();
    }

    // ------------------------------------------------------------ Зависимости

    public function addDependency(int $dependsOnId): void
    {
        $task = $this->task();
        $dependsOn = $this->project->tasks()->find($dependsOnId);

        if (! $dependsOn || $task->is($dependsOn)) {
            return;
        }

        if ($task->dependsOn()->whereKey($dependsOn->id)->exists()) {
            $this->dispatch('toast', message: 'Такая зависимость уже есть.', type: 'error');

            return;
        }

        if ($task->wouldCreateDependencyCycle($dependsOn)) {
            $this->dispatch('toast', message: "Нельзя добавить: {$dependsOn->full_number} уже зависит от этой задачи (цикл).", type: 'error');

            return;
        }

        $task->dependsOn()->attach($dependsOn->id);
        $this->depQuery = '';
        $this->dispatch('task-saved');
    }

    public function removeDependency(int $dependsOnId): void
    {
        $this->task()->dependsOn()->detach($dependsOnId);
        $this->dispatch('task-saved');
    }

    // ----------------------------------------------------------------- Render

    /** Комментарии и смены статусов одной хронологической лентой */
    private function buildFeed(Task $task): Collection
    {
        $comments = collect();
        $logs = collect();

        if ($this->showComments) {
            $comments = $task->comments()->with('user')->get()
                ->map(fn ($comment) => ['kind' => 'comment', 'at' => $comment->created_at, 'item' => $comment]);
        }

        if ($this->showHistory) {
            $logs = $task->statusLogs()->with(['user', 'fromStatus', 'toStatus'])->get()
                ->map(fn ($log) => ['kind' => 'status', 'at' => $log->created_at, 'item' => $log]);
        }

        return $comments->concat($logs)->sortBy('at')->values();
    }

    public function render()
    {
        $task = null;
        $feed = collect();
        $timeLogs = collect();
        $depOptions = collect();

        if ($this->show && $this->taskId) {
            $task = $this->task();
            $feed = $this->buildFeed($task);
            $timeLogs = $task->timeLogs()->with('user')->orderByDesc('logged_date')->orderByDesc('id')->get();

            if (mb_strlen(trim($this->depQuery)) >= 1) {
                $query = trim($this->depQuery);
                $existingIds = $task->dependsOn->pluck('id')->push($task->id);

                $depOptions = $this->project->tasks()
                    ->whereKeyNot($existingIds)
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'ilike', "%{$query}%")
                            ->orWhereRaw('cast(number as text) like ?', ["%{$query}%"]);
                    })
                    ->with('status')
                    ->limit(7)
                    ->get();
            }
        }

        return view('livewire.tasks.task-modal', [
            'task' => $task,
            'feed' => $feed,
            'timeLogs' => $timeLogs,
            'depOptions' => $depOptions,
            'statuses' => $this->project->statuses,
            'members' => $this->project->members()->orderBy('name')->get(),
            'priorities' => $this->project->priorities,
            'totalMinutes' => $timeLogs->sum('minutes'),
        ]);
    }
}
