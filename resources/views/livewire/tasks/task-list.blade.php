<div class="flex h-full flex-col">
    <x-slot name="header">
        <div class="flex h-12 items-center gap-1.5 px-4 text-sm">
            <a href="{{ route('projects.index') }}" class="text-yt-muted hover:text-yt-link">Доски</a>
            <span class="text-yt-muted">/</span>
            <h1 class="truncate text-yt-text">{{ $project->name }}</h1>

            {{-- Переключатель представления: канбан ↔ список --}}
            <div class="ml-3 flex items-center rounded-md bg-[rgba(81,95,104,0.25)] p-0.5">
                <a href="{{ route('projects.board', $project) }}" wire:navigate
                   class="rounded px-3 py-0.5 text-[13px] text-yt-muted hover:text-yt-text">Канбан</a>
                <span class="rounded bg-[rgba(81,95,104,0.7)] px-3 py-0.5 text-[13px] text-yt-text">Список</span>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <x-filter-input event="list-filter" :meta="$filterMeta" :value="$filter" width="w-[380px]"
                                placeholder="Фильтр: статус: работе исполнитель: я текст…" />

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="yt-btn-primary !py-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Создать
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false" style="display: none;"
                         class="absolute right-0 z-40 mt-1 w-44 rounded-lg border border-yt-border bg-yt-panel py-1 shadow-modal">
                        @foreach (['epic' => 'Эпик', 'story' => 'История', 'task' => 'Задача'] as $typeValue => $typeLabel)
                            <button @click="open = false; Livewire.dispatch('open-create-task', { type: '{{ $typeValue }}' })"
                                    class="block w-full px-3 py-1.5 text-left text-sm text-yt-text hover:bg-yt-hover">
                                {{ $typeLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $typeColors = ['epic' => '#9C7FE0', 'story' => '#61A5F2', 'task' => '#7A8087'];
        $plural = fn (int $n) => ($n % 10 === 1 && $n % 100 !== 11)
            ? 'задача'
            : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? 'задачи' : 'задач');
    @endphp

    <div class="min-h-0 flex-1 overflow-auto">
        <div class="px-4 pb-1 pt-3 text-xs text-yt-muted">
            {{ $total }} {{ $plural($total) }}
            <span wire:loading class="ml-2 text-yt-faint">Загрузка…</span>
        </div>

        <table class="w-full min-w-[900px] border-collapse text-sm">
            <thead class="sticky top-0 z-10 bg-yt-bg">
                <tr class="border-b border-yt-border-soft text-left text-xs text-yt-muted">
                    <th class="px-4 py-2 font-medium">Номер</th>
                    <th class="px-2 py-2 font-medium">Тип</th>
                    <th class="w-full px-2 py-2 font-medium">Название</th>
                    <th class="px-2 py-2 font-medium">Статус</th>
                    <th class="px-2 py-2 font-medium">Приоритет</th>
                    <th class="px-2 py-2 font-medium">Исполнитель</th>
                    <th class="px-2 py-2 font-medium">Автор</th>
                    <th class="px-2 py-2 font-medium">Срок</th>
                    <th class="px-4 py-2 font-medium">Обновлена</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr wire:key="task-row-{{ $task->id }}" x-data
                        x-on:click="Livewire.dispatch('open-task', { taskId: {{ $task->id }} })"
                        class="cursor-pointer border-b border-yt-border-soft hover:bg-yt-hover">
                        <td class="whitespace-nowrap px-4 py-2 text-yt-muted {{ $task->status->is_final ? 'line-through' : '' }}">{{ $task->full_number }}</td>
                        <td class="whitespace-nowrap px-2 py-2">
                            <span class="rounded-full px-2 py-px text-[11px] font-medium"
                                  style="background: {{ $typeColors[$task->type->value] }}26; color: {{ $typeColors[$task->type->value] }}">{{ $task->type->label() }}</span>
                        </td>
                        <td class="px-2 py-2 {{ $task->status->is_final ? 'text-yt-muted' : 'text-yt-text' }}">
                            {{ $task->title }}
                            @if ($task->parent)
                                <span class="ml-1 text-xs text-yt-faint" title="Родитель: {{ $task->parent->title }}">в {{ $task->parent->full_number }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-2 py-2 {{ $task->status->is_final ? 'text-yt-success' : 'text-yt-muted' }}">{{ $task->status->name }}</td>
                        <td class="whitespace-nowrap px-2 py-2">
                            <span class="inline-flex items-center gap-1.5 text-xs text-yt-muted">
                                <span class="h-[7px] w-[7px] rounded-full" style="background: {{ $task->priority->color }}; box-shadow: 0 0 6px 1px {{ $task->priority->color }}B3"></span>
                                {{ $task->priority->name }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-2 py-2 text-yt-muted">{{ $task->assignee?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-2 py-2 text-yt-muted">{{ $task->creator?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-2 py-2 text-xs {{ $task->due_date && $task->due_date->isPast() && ! $task->status->is_final ? 'font-medium text-yt-danger' : 'text-yt-faint' }}">
                            {{ $task->due_date?->format('d.m.Y') ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-2 text-xs text-yt-faint" title="{{ $task->updated_at->format('d.m.Y H:i') }}">{{ $task->updated_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-sm text-yt-faint">Ничего не найдено. Попробуйте изменить фильтр.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($hasMore)
            {{-- Сентинел бесконечной прокрутки: доехал до низа — грузим ещё --}}
            <div x-data
                 x-init="new IntersectionObserver((entries) => { if (entries[0].isIntersecting) $wire.loadMore() }, { rootMargin: '300px' }).observe($el)"
                 class="py-4 text-center text-xs text-yt-faint">
                Загружаем ещё…
            </div>
        @endif
    </div>

    <livewire:tasks.task-create-modal :project="$project" />
</div>
