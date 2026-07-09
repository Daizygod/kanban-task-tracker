<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Запись истории задачи: создание и изменения полей.
 * Смены статусов живут отдельно в TaskStatusLog.
 */
class TaskActivity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'task_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Человекочитаемое название изменённого поля */
    public function fieldLabel(): string
    {
        return match ($this->field) {
            'created' => 'создал(а)',
            'title' => 'название',
            'description' => 'описание',
            'assignee' => 'исполнитель',
            'priority' => 'приоритет',
            'due_date' => 'срок',
            'parent' => 'родитель',
            default => $this->field,
        };
    }
}
