<?php

namespace App\Livewire\Tasks;

use App\Exceptions\StatusChangeBlockedException;
use App\Livewire\Concerns\SearchesMentions;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\UserNotification;
use App\Services\Centrifugo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskModal extends Component
{
    use SearchesMentions;
    /** Может отсутствовать (страница «Учёт времени») — тогда берётся из открываемой задачи */
    public ?Project $project = null;

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

    public ?int $timeWorkTypeId = null;

    // Зависимости
    public string $depQuery = '';

    // Фильтры ленты активности
    public bool $showComments = true;

    public bool $showHistory = true;

    #[On('open-task')]
    public function open(int $taskId): void
    {
        $task = Task::with('project')->find($taskId);

        if (! $task || ! $task->project->hasMember(Auth::user())) {
            return;
        }

        $this->project = $task->project;

        $this->reset('commentBody', 'timeValue', 'timeDescription', 'timeWorkTypeId', 'depQuery', 'editingDescription');
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
            ->with(['status', 'priority', 'assignee', 'creator', 'parent', 'children.status', 'dependsOn.status', 'dependents.status', 'boards'])
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
        $task = $this->task();
        $userId = $userId ?: null;

        if ($userId && $userId !== $task->assignee_id) {
            UserNotification::send($userId, UserNotification::TYPE_ASSIGNED, $task);
        }

        $task->update(['assignee_id' => $userId]);
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
        $task = $this->task();
        $description = trim($this->descriptionDraft) ?: null;

        // Уведомляем только новых упомянутых — не спамим при каждом сохранении
        UserNotification::sendMentions($description, $task, previousText: $task->description);

        $task->update(['description' => $description]);
        $this->editingDescription = false;
    }

    // ------------------------------------------------------------ Комментарии

    public function addComment(): void
    {
        $this->validate(
            ['commentBody' => ['required', 'string', 'max:10000']],
            ['commentBody.required' => 'Комментарий не может быть пустым.'],
        );

        $task = $this->task();
        $body = trim($this->commentBody);

        $task->comments()->create([
            'user_id' => Auth::id(),
            'body' => $body,
        ]);

        UserNotification::sendMentions($body, $task);

        $this->commentBody = '';
    }

    // ----------------------------------------------------------- Учёт времени

    public function addTimeLog(): void
    {
        $this->validate([
            'timeValue' => ['required', 'numeric', 'gt:0', 'max:10000'],
            'timeUnit' => ['required', Rule::in(['hours', 'minutes'])],
            'timeDate' => ['required', 'date'],
            'timeWorkTypeId' => ['required', Rule::exists('work_types', 'id')->where('project_id', $this->project->id)],
            'timeDescription' => ['nullable', 'string', 'max:1000'],
        ], messages: ['timeWorkTypeId.required' => 'Выберите тип работы.'], attributes: ['timeValue' => 'время', 'timeDate' => 'дата', 'timeWorkTypeId' => 'тип работы']);

        $minutes = (int) round(
            $this->timeUnit === 'hours' ? ((float) $this->timeValue) * 60 : (float) $this->timeValue
        );

        if ($minutes < 1) {
            $this->addError('timeValue', 'Слишком маленький интервал.');

            return;
        }

        $this->task()->timeLogs()->create([
            'user_id' => Auth::id(),
            'work_type_id' => $this->timeWorkTypeId,
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

    // ---------------------------------------------------- Видимость на досках

    public function toggleBoard(int $boardId): void
    {
        $board = $this->project->boards()->where('is_default', false)->findOrFail($boardId);

        $this->task()->boards()->toggle($board->id);
        $this->publish('task-updated');
        $this->dispatch('task-saved');
    }

    // ----------------------------------------------------------------- Render

    /** Комментарии, смены статусов и история полей одной хронологической лентой */
    private function buildFeed(Task $task): Collection
    {
        $comments = collect();
        $logs = collect();
        $activities = collect();

        if ($this->showComments) {
            $comments = $task->comments()->with('user')->get()
                ->map(fn ($comment) => ['kind' => 'comment', 'at' => $comment->created_at, 'item' => $comment]);
        }

        if ($this->showHistory) {
            $logs = $task->statusLogs()->with(['user', 'fromStatus', 'toStatus'])->get()
                ->map(fn ($log) => ['kind' => 'status', 'at' => $log->created_at, 'item' => $log]);

            $activities = $task->activities()->with('user')->get()
                ->map(fn ($activity) => ['kind' => 'activity', 'at' => $activity->created_at, 'item' => $activity]);
        }

        return $comments->concat($logs)->concat($activities)->sortBy('at')->values();
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
            $timeLogs = $task->timeLogs()->with(['user', 'workType'])->orderByDesc('logged_date')->orderByDesc('id')->get();

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
            'statuses' => $this->project?->statuses ?? collect(),
            'members' => $this->project?->members()->orderBy('name')->get() ?? collect(),
            'priorities' => $this->project?->priorities ?? collect(),
            'workTypes' => $this->project?->workTypes ?? collect(),
            'customBoards' => $this->project?->boards()->where('is_default', false)->get() ?? collect(),
            'totalMinutes' => $timeLogs->sum('minutes'),
        ]);
    }
}
