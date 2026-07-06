<?php

namespace App\Models;

use App\Support\Mentions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserNotification extends Model
{
    public const TYPE_MENTIONED = 'mentioned';

    public const TYPE_ASSIGNED = 'assigned';

    protected $fillable = [
        'user_id',
        'actor_id',
        'task_id',
        'type',
        'context',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** Уведомление получателю от текущего пользователя; самому себе не шлём */
    public static function send(User|int $recipient, string $type, Task $task, ?string $context = null): void
    {
        $recipientId = $recipient instanceof User ? $recipient->id : $recipient;

        if ($recipientId === Auth::id()) {
            return;
        }

        self::create([
            'user_id' => $recipientId,
            'actor_id' => Auth::id(),
            'task_id' => $task->id,
            'type' => $type,
            'context' => $context !== null ? Str::limit(trim($context), 180) : null,
        ]);
    }

    /**
     * Уведомления всем участникам проекта, упомянутым в тексте через @логин.
     * $previousText позволяет не слать повторно тем, кто уже был упомянут
     * (например, при редактировании описания).
     */
    public static function sendMentions(?string $text, Task $task, ?string $previousText = null): void
    {
        $usernames = array_diff(Mentions::usernames($text), Mentions::usernames($previousText));

        if ($usernames === []) {
            return;
        }

        $users = $task->project->members()
            ->whereIn('username', $usernames)
            ->get();

        foreach ($users as $user) {
            self::send($user, self::TYPE_MENTIONED, $task, $text);
        }
    }
}
