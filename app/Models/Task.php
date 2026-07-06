<?php

namespace App\Models;

use App\Enums\TaskType;
use App\Exceptions\StatusChangeBlockedException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'type',
        'parent_id',
        'status_id',
        'title',
        'description',
        'priority_id',
        'board_order',
        'created_by',
        'assignee_id',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'type' => TaskType::class,
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Сквозной номер в рамках проекта: блокируем строку проекта,
        // чтобы конкурентные создания не получили одинаковый номер
        static::creating(function (Task $task) {
            if ($task->number === null) {
                $task->number = DB::transaction(function () use ($task) {
                    $project = Project::whereKey($task->project_id)->lockForUpdate()->first();
                    $number = $project->next_task_number;
                    $project->increment('next_task_number');

                    return $number;
                });
            }

            // Номер монотонно растёт в рамках проекта, поэтому новая карточка
            // встаёт в конец своей ячейки на доске
            if (! $task->board_order) {
                $task->board_order = $task->number;
            }
        });

        // Новая задача видна на всех пользовательских досках проекта
        static::created(function (Task $task) {
            $boardIds = Board::where('project_id', $task->project_id)
                ->where('is_default', false)
                ->pluck('id');

            if ($boardIds->isNotEmpty()) {
                $task->boards()->attach($boardIds);
            }
        });
    }

    protected function fullNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->project->key.'-'.$this->number,
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** Задачи, от которых зависит эта задача */
    public function dependsOn(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')
            ->withTimestamps();
    }

    /** Задачи, которые зависят от этой задачи */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')
            ->withTimestamps();
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TaskStatusLog::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /** Пользовательские доски, на которых задача видима */
    public function boards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class)->withTimestamps();
    }

    /**
     * Незакрытые задачи, от которых зависит эта задача
     * (блокируют перевод в финальный статус).
     *
     * @return Collection<int, Task>
     */
    public function openBlockers(): Collection
    {
        return $this->dependsOn()
            ->with(['status', 'project'])
            ->get()
            ->reject(fn (Task $task) => $task->status->is_final)
            ->values();
    }

    /**
     * Единая точка смены статуса: валидация зависимостей + запись в лог.
     *
     * @throws StatusChangeBlockedException
     */
    public function moveToStatus(Status $status, User $user): void
    {
        if ($status->project_id !== $this->project_id) {
            throw new InvalidArgumentException('Статус принадлежит другому проекту.');
        }

        if ($this->status_id === $status->id) {
            return;
        }

        if ($status->is_final) {
            $blockers = $this->openBlockers();

            if ($blockers->isNotEmpty()) {
                throw new StatusChangeBlockedException($this, $blockers);
            }
        }

        $fromStatusId = $this->status_id;

        $this->status_id = $status->id;
        $this->save();

        $this->statusLogs()->create([
            'user_id' => $user->id,
            'from_status_id' => $fromStatusId,
            'to_status_id' => $status->id,
        ]);
    }

    /**
     * Появится ли цикл, если эта задача станет зависеть от $candidate.
     */
    public function wouldCreateDependencyCycle(Task $candidate): bool
    {
        if ($candidate->id === $this->id) {
            return true;
        }

        // BFS по графу зависимостей от кандидата: если из него достижима
        // текущая задача, новая связь замкнёт цикл
        $frontier = [$candidate->id];
        $visited = [];

        while ($frontier !== []) {
            if (in_array($this->id, $frontier, true)) {
                return true;
            }

            $visited = array_merge($visited, $frontier);

            $frontier = DB::table('task_dependencies')
                ->whereIn('task_id', $frontier)
                ->whereNotIn('depends_on_task_id', $visited)
                ->pluck('depends_on_task_id')
                ->unique()
                ->all();
        }

        return false;
    }

    /**
     * Валидация иерархии: story может ссылаться только на epic,
     * task — только на story, epic — ни на что.
     */
    public function validateParent(?Task $parent): void
    {
        if ($parent === null) {
            return;
        }

        $allowed = $this->type->allowedParentType();

        if ($allowed === null) {
            throw new InvalidArgumentException("У типа «{$this->type->label()}» не может быть родителя.");
        }

        if ($parent->type !== $allowed) {
            throw new InvalidArgumentException(
                "Родителем для «{$this->type->label()}» может быть только «{$allowed->label()}»."
            );
        }

        if ($parent->project_id !== $this->project_id) {
            throw new InvalidArgumentException('Родитель должен быть из того же проекта.');
        }
    }
}
