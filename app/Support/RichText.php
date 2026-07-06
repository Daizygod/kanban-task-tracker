<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class RichText
{
    /**
     * Экранирует текст и оживляет упоминания: @логин подсвечивается,
     * номер задачи (KEY-123) становится кнопкой, открывающей модалку.
     * Выводить внутри контейнера с whitespace-pre-wrap.
     */
    public static function render(?string $text, Project $project): HtmlString
    {
        $html = e($text ?? '');

        // Номера задач: своего проекта и других проектов пользователя
        $html = preg_replace_callback('/\b([A-Z]{2,10})-(\d{1,9})\b/u', function (array $m) use ($project) {
            $task = self::findTask($m[1], (int) $m[2], $project);

            if (! $task) {
                return $m[0];
            }

            // stopPropagation: упоминание живёт внутри кликабельных контейнеров
            // (описание открывает редактор по клику)
            return '<button type="button" class="text-yt-link hover:underline"'
                .' onclick="event.stopPropagation(); Livewire.dispatch(\'open-task\', { taskId: '.$task->id.' })">'
                .e($m[0]).'</button>';
        }, $html);

        // @логины участников проекта
        $members = $project->members->keyBy(fn ($user) => mb_strtolower((string) $user->username));

        $html = preg_replace_callback('/@('.Mentions::USERNAME_PATTERN.')/iu', function (array $m) use ($members) {
            $user = $members[mb_strtolower($m[1])] ?? null;

            if (! $user) {
                return $m[0];
            }

            return '<span class="yt-mention" title="'.e($user->name).'">@'.e($user->username).'</span>';
        }, $html);

        return new HtmlString($html);
    }

    private static function findTask(string $key, int $number, Project $project): ?object
    {
        if ($key === $project->key) {
            return $project->tasks()->where('number', $number)->first(['id']);
        }

        $other = Project::where('key', $key)->first();

        if (! $other || ! Auth::user() || ! $other->hasMember(Auth::user())) {
            return null;
        }

        return $other->tasks()->where('number', $number)->first(['id']);
    }
}
