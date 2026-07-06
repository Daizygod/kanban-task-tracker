<div class="flex h-full flex-col"
     x-data="boardChannel"
     data-channel="project.{{ $project->id }}.board"
     data-tab="{{ $tab }}"
     data-status-ids="{{ $statuses->pluck('id')->toJson() }}">

    <x-slot name="header">
        {{-- Строка 1: хлебные крошки + поиск --}}
        <div class="flex h-12 items-center gap-1.5 px-4 text-sm">
            <a href="{{ route('projects.index') }}" class="text-yt-muted hover:text-yt-link">Доски</a>
            <span class="text-yt-muted">/</span>
            <h1 class="truncate text-yt-text">{{ $project->name }}</h1>
            <svg class="h-3.5 w-3.5 text-yt-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>

            <div class="relative ml-auto">
                <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-yt-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                {{-- Слот шапки живёт вне DOM Livewire-компонента, поэтому глобальный dispatch --}}
                <input type="text" x-data
                       x-on:input.debounce.400ms="Livewire.dispatch('board-filter', { value: $event.target.value })"
                       placeholder="Фильтр карточек на доске"
                       class="h-8 w-[280px] rounded-lg border-yt-border bg-transparent pl-8 pr-3 text-sm text-yt-text placeholder-yt-muted focus:border-yt-accent focus:ring-1 focus:ring-yt-accent">
            </div>
        </div>

        {{-- Строка 2: тулбар доски --}}
        <div class="flex h-11 items-center gap-0.5 px-4">
            {{-- Выбор доски: дефолтная показывает все задачи, свои — только включённые --}}
            <select x-data x-on:change="Livewire.dispatch('board-select', { boardId: Number($event.target.value) })"
                    class="mr-2 h-8 w-44 rounded border-yt-border bg-transparent px-2 text-sm text-yt-text focus:border-yt-accent focus:ring-1 focus:ring-yt-accent"
                    title="Доска">
                @foreach ($boards as $board)
                    <option value="{{ $board->id }}" @selected($board->id === $currentBoard->id)>{{ $board->name }}</option>
                @endforeach
            </select>

            @php
                // «?board=» внутри инлайнового @php(...) ломает компиляцию Blade,
                // поэтому блочная форма
                $boardQuery = $currentBoard->is_default ? '' : '?board='.$currentBoard->id;
            @endphp
            @foreach (['epics' => 'Эпики', 'stories' => 'Истории', 'tasks' => 'Задачи'] as $tabKey => $tabLabel)
                <a href="{{ route('projects.board', [$project, $tabKey]).$boardQuery }}" wire:navigate
                   class="rounded px-3 py-1 text-sm {{ $tab === $tabKey ? 'bg-[rgba(81,95,104,0.5)] text-yt-text' : 'text-yt-muted hover:bg-[rgba(81,95,104,0.3)] hover:text-yt-text' }}">
                    {{ $tabLabel }}
                </a>
            @endforeach

            <div class="ml-auto flex items-center gap-2">
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
                <a href="{{ route('projects.settings', $project) }}" class="rounded p-1.5 text-yt-muted hover:bg-[rgba(81,95,104,0.3)] hover:text-yt-text" title="Настройки проекта">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="min-h-0 flex-1 overflow-auto bg-yt-board pb-6">
        <div class="min-w-fit">
            {{-- Заголовки колонок: «‹ Название N», клик по шеврону сворачивает колонку (клиентски) --}}
            <div class="sticky top-0 z-20 border-b border-yt-board-border bg-yt-board">
                <div class="grid" style="grid-template-columns: {{ $gridTemplate }};" :style="gridStyle">
                    @foreach ($statuses as $status)
                        <div class="flex items-center gap-1.5 overflow-hidden px-2 py-2.5 {{ ! $loop->first ? 'border-l border-yt-board-border' : '' }}">
                            <button x-on:click="toggleCol({{ $status->id }})"
                                    class="shrink-0 rounded p-0.5 text-yt-faint hover:bg-yt-hover hover:text-yt-text"
                                    :title="isColCollapsed({{ $status->id }}) ? 'Развернуть колонку' : 'Свернуть колонку'">
                                <svg class="h-3 w-3 transition-transform" :class="isColCollapsed({{ $status->id }}) && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                            </button>
                            <span class="truncate text-sm text-yt-text">{{ $status->name }}</span>
                            <template x-if="!isColCollapsed({{ $status->id }})">
                                <span class="flex min-w-0 flex-1 items-center gap-1.5">
                                    @if ($status->is_final)
                                        <svg class="h-3.5 w-3.5 shrink-0 text-yt-success" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    @endif
                                    <span class="ml-auto text-xs text-yt-muted">{{ $statusCounts[$status->id] }}</span>
                                </span>
                            </template>
                        </div>
                    @endforeach
                </div>
            </div>

            @php
                $plural = fn (int $n) => ($n % 10 === 1 && $n % 100 !== 11)
                    ? 'карточка'
                    : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? 'карточки' : 'карточек');
                $maxStatusCount = max(array_values($statusCounts) ?: [0]);
            @endphp
            <div class="flex items-end px-3 pt-2">
                {{-- Мини-гистограмма: сколько карточек в каждом статусе --}}
                <div class="flex items-end gap-[3px]">
                    @foreach ($statuses as $status)
                        @php
                            $count = $statusCounts[$status->id];
                            $barHeight = $maxStatusCount > 0 && $count > 0
                                ? max(2, (int) round($count / $maxStatusCount * 22))
                                : 0;
                        @endphp
                        <span class="relative h-[22px] w-[9px] overflow-hidden rounded-sm bg-white/10"
                              title="{{ $count }} {{ $plural($count) }} в статусе «{{ $status->name }}»"
                              wire:key="progress-{{ $status->id }}">
                            <span class="absolute inset-x-0 bottom-0 rounded-sm bg-yt-accent" style="height: {{ $barHeight }}px"></span>
                        </span>
                    @endforeach
                </div>

                <span class="ml-auto text-xs text-yt-faint">{{ $totalCards }} {{ $plural($totalCards) }}</span>
            </div>

            {{-- Строки --}}
            @foreach ($rows as $row)
                <div wire:key="row-{{ $tab }}-{{ $row['key'] }}">
                    @if ($tab !== 'tasks')
                        <div class="mt-1 flex items-center gap-2 border-y border-yt-board-border bg-yt-swimlane px-3 py-1.5">
                            <button x-on:click="toggleRow('{{ $row['key'] }}')"
                                    class="shrink-0 rounded p-0.5 text-yt-faint hover:bg-yt-hover hover:text-yt-text"
                                    :title="isRowCollapsed('{{ $row['key'] }}') ? 'Развернуть строку' : 'Свернуть строку'">
                                <svg class="h-3 w-3 transition-transform" :class="isRowCollapsed('{{ $row['key'] }}') && '-rotate-90'" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                            @if ($row['header'])
                                <button wire:click="openTask({{ $row['header']->id }})"
                                        class="flex min-w-0 items-center gap-2 text-sm">
                                    <span class="text-yt-link hover:underline {{ $row['header']->status->is_final ? 'line-through opacity-70' : '' }}">{{ $row['header']->full_number }}</span>
                                    <span class="truncate font-medium {{ $row['header']->status->is_final ? 'text-yt-muted line-through' : '' }}">{{ $row['header']->title }}</span>
                                </button>
                            @else
                                <span class="text-sm font-medium text-yt-muted">{{ $orphanRowTitle }}</span>
                            @endif
                            <span class="text-xs text-yt-faint">{{ $row['cards']->flatten()->count() }}</span>
                        </div>
                    @endif

                    <div class="grid" style="grid-template-columns: {{ $gridTemplate }};" :style="gridStyle"
                         @if ($tab !== 'tasks') x-show="!isRowCollapsed('{{ $row['key'] }}')" @endif>
                        @foreach ($statuses as $status)
                            <div class="group/cell flex flex-col px-1 py-2 {{ ! $loop->first ? 'border-l border-yt-board-border' : '' }}">
                                {{-- Развёрнутая колонка: обычные карточки + dnd.
                                     flex-1 растягивает drop-зону на всю высоту строки,
                                     чтобы в короткую колонку можно было бросить карточку где угодно --}}
                                <div x-data="kanbanColumn"
                                     x-show="!isColCollapsed({{ $status->id }})"
                                     data-status-id="{{ $status->id }}"
                                     @if ($tab !== 'tasks')
                                         data-parent-scope="row"
                                         data-parent-id="{{ $row['header']?->id }}"
                                     @endif
                                     wire:key="cell-{{ $tab }}-{{ $row['key'] }}-{{ $status->id }}"
                                     class="min-h-[56px] flex-1 space-y-1.5">
                                    @foreach ($row['cards'][$status->id] ?? [] as $card)
                                        <x-task-card :task="$card" wire:key="card-{{ $card->id }}" />
                                    @endforeach
                                </div>

                                {{-- Быстрое создание карточки в этой ячейке: статус из колонки,
                                     родитель из строки. Живёт вне dnd-списка, чтобы не таскался --}}
                                @php($quickParentId = $tab !== 'tasks' ? ($row['header']?->id ?? 'null') : 'null')
                                <div x-show="!isColCollapsed({{ $status->id }})"
                                     x-data="{ adding: false, title: '' }"
                                     wire:key="quick-{{ $tab }}-{{ $row['key'] }}-{{ $status->id }}"
                                     class="mt-1.5">
                                    {{-- Плюсик виден всегда, подпись появляется при наведении на ячейку --}}
                                    <button x-show="!adding" x-on:click="adding = true; $nextTick(() => $refs.quickInput.focus())"
                                            class="flex w-full items-center gap-1.5 rounded px-2 py-1 text-xs text-yt-faint hover:bg-yt-hover hover:text-yt-text"
                                            title="{{ $quickCardLabel }} в «{{ $status->name }}»">
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        <span class="hidden group-hover/cell:inline">{{ $quickCardLabel }}</span>
                                    </button>
                                    <div x-show="adding" x-cloak>
                                        <input type="text" x-ref="quickInput" x-model="title"
                                               placeholder="Название, Enter — создать"
                                               class="yt-input px-2 py-1.5"
                                               x-on:keydown.enter.prevent="if (title.trim()) { $wire.quickCreate({{ $status->id }}, {{ $quickParentId }}, title); title = ''; adding = false }"
                                               x-on:keydown.escape.stop="adding = false; title = ''"
                                               x-on:blur="if (!title.trim()) { adding = false }">
                                    </div>
                                </div>

                                {{-- Свёрнутая колонка: карточки цветными квадратиками --}}
                                @php($cellCards = $row['cards'][$status->id] ?? collect())
                                <div x-show="isColCollapsed({{ $status->id }})" x-cloak
                                     wire:key="mini-cell-{{ $tab }}-{{ $row['key'] }}-{{ $status->id }}"
                                     class="flex flex-wrap content-start gap-1 px-1 py-1">
                                    @foreach ($cellCards->take(24) as $mini)
                                        <span class="h-2.5 w-2.5 cursor-pointer rounded-[2px] transition-opacity hover:opacity-70"
                                              style="background: {{ $mini->priority->color }}; box-shadow: 0 0 6px {{ $mini->priority->color }}B3"
                                              title="{{ $mini->full_number }} {{ $mini->title }}"
                                              wire:click="openTask({{ $mini->id }})"
                                              wire:key="mini-{{ $mini->id }}"></span>
                                    @endforeach
                                    @if ($cellCards->count() > 24)
                                        <span class="w-full text-[11px] leading-tight text-yt-faint">+ {{ $cellCards->count() - 24 }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Модалка просмотра задачи живёт в layouts.app --}}
    <livewire:tasks.task-create-modal :project="$project" />
</div>
