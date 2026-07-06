<?php

namespace App\Livewire\Concerns;

/**
 * Автокомплит для @-упоминаний: участники проекта и задачи.
 * Компонент должен иметь свойство $project (может быть null).
 */
trait SearchesMentions
{
    /** @return list<array{kind: string, value: string, label: string, hint: string}> */
    public function searchMentions(string $query = ''): array
    {
        if (! $this->project) {
            return [];
        }

        $query = trim($query);

        $users = $this->project->members()
            ->when($query !== '', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('username', 'ilike', "%{$query}%")
                ->orWhere('name', 'ilike', "%{$query}%")))
            ->orderBy('username')
            ->limit(5)
            ->get()
            ->map(fn ($user) => [
                'kind' => 'user',
                'value' => $user->username,
                'label' => '@'.$user->username,
                'hint' => $user->name,
            ]);

        $tasks = $this->project->tasks()
            ->when($query !== '', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('title', 'ilike', "%{$query}%")
                // key провалидирован как [A-Z]{3}, интерполяция безопасна
                ->orWhereRaw("'{$this->project->key}-' || number ilike ?", ["%{$query}%"])))
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn ($task) => [
                'kind' => 'task',
                'value' => $task->full_number,
                'label' => $task->full_number,
                'hint' => $task->title,
            ]);

        return $users->concat($tasks)->values()->all();
    }
}
