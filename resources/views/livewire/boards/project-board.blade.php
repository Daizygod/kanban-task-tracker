<div class="flex h-full flex-col"
     x-data="boardChannel"
     data-channel="project.{{ $project->id }}.board">

    <x-slot name="header">
        <div class="flex min-w-0 items-center gap-2 text-sm">
            <h1 class="truncate font-semibold">{{ $project->name }}</h1>
            <span class="rounded bg-yt-accent/15 px-1.5 py-0.5 text-[11px] font-bold tracking-wider text-yt-accent-hover">{{ $project->key }}</span>
        </div>

        <nav class="ml-8 flex gap-1">
            @foreach (['epics' => 'Эпики', 'stories' => 'Истории', 'tasks' => 'Задачи'] as $tabKey => $tabLabel)
                <a href="{{ route('projects.board', [$project, $tabKey]) }}" wire:navigate
                   class="rounded px-3 py-1.5 text-sm {{ $tab === $tabKey ? 'bg-yt-hover font-medium text-yt-text' : 'text-yt-muted hover:bg-yt-hover hover:text-yt-text' }}">
                    {{ $tabLabel }}
                </a>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-2">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="yt-btn-primary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Создать
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open" @click.outside="open = false" style="display: none;"
                     class="absolute right-0 z-40 mt-1 w-44 rounded-md border border-yt-border bg-yt-panel py-1 shadow-modal">
                    @foreach (['epic' => 'Эпик', 'story' => 'История', 'task' => 'Задача'] as $typeValue => $typeLabel)
                        <button @click="open = false; Livewire.dispatch('open-create-task', { type: '{{ $typeValue }}' })"
                                class="block w-full px-3 py-1.5 text-left text-sm text-yt-text hover:bg-yt-hover">
                            {{ $typeLabel }}
                        </button>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('projects.settings', $project) }}" class="rounded p-1.5 text-yt-muted hover:bg-yt-hover hover:text-yt-text" title="Настройки проекта">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
            </a>
        </div>
    </x-slot>

    <div class="min-h-0 flex-1 overflow-auto p-4">
        <div class="min-w-max">
            {{-- Заголовки колонок --}}
            <div class="sticky top-0 z-20 grid gap-3 bg-yt-bg pb-3"
                 style="grid-template-columns: repeat({{ $statuses->count() }}, 280px);">
                @foreach ($statuses as $status)
                    <div class="flex items-center gap-2 rounded bg-yt-surface px-3 py-2 text-xs font-semibold uppercase tracking-wide text-yt-muted">
                        {{ $status->name }}
                        @if ($status->is_final)
                            <svg class="h-3.5 w-3.5 text-yt-success" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" title="Финальный статус"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Строки --}}
            @foreach ($rows as $row)
                <div wire:key="row-{{ $tab }}-{{ $row['key'] }}" class="mb-2">
                    @if ($tab !== 'tasks')
                        <div class="flex items-center gap-2 py-2">
                            @if ($row['header'])
                                <button wire:click="openTask({{ $row['header']->id }})"
                                        class="flex items-center gap-2 rounded px-1.5 py-0.5 text-sm hover:bg-yt-hover">
                                    <span class="text-xs text-yt-faint">{{ $row['header']->full_number }}</span>
                                    <span class="font-medium">{{ $row['header']->title }}</span>
                                </button>
                                @if ($row['header']->status->is_final)
                                    <span class="yt-chip text-yt-success">завершён</span>
                                @endif
                            @else
                                <span class="px-1.5 text-sm font-medium text-yt-faint">{{ $orphanRowTitle }}</span>
                            @endif
                            <span class="text-xs text-yt-faint">{{ $row['cards']->flatten()->count() }}</span>
                        </div>
                    @endif

                    <div class="grid gap-3" style="grid-template-columns: repeat({{ $statuses->count() }}, 280px);">
                        @foreach ($statuses as $status)
                            <div class="rounded bg-yt-surface/60 p-2">
                                <div x-data="kanbanColumn"
                                     data-status-id="{{ $status->id }}"
                                     data-row-key="{{ $tab }}-{{ $row['key'] }}"
                                     wire:key="cell-{{ $tab }}-{{ $row['key'] }}-{{ $status->id }}"
                                     class="min-h-[44px] space-y-2">
                                    @foreach ($row['cards'][$status->id] ?? [] as $card)
                                        <x-task-card :task="$card" wire:key="card-{{ $card->id }}" />
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <livewire:tasks.task-modal :project="$project" />
    <livewire:tasks.task-create-modal :project="$project" />
</div>
