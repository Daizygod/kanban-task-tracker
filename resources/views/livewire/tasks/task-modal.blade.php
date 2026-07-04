<div>
    @if ($show && $task)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-4 pt-10"
             wire:click.self="close"
             x-data x-on:keydown.escape.window="$wire.close()">
            <div class="flex w-full max-w-4xl flex-col rounded-lg border border-yt-border bg-yt-panel shadow-modal">
                {{-- Шапка --}}
                <div class="flex items-center gap-3 border-b border-yt-border-soft px-5 py-3">
                    <span class="text-xs font-medium {{ $task->status->is_final ? 'text-yt-faint line-through' : 'text-yt-muted' }}">{{ $task->full_number }}</span>
                    <span class="yt-chip">{{ $task->type->label() }}</span>
                    @if ($task->parent)
                        <button wire:click="open({{ $task->parent->id }})" class="yt-chip hover:bg-yt-hover" title="Родитель">
                            ↑ {{ $task->parent->full_number }}
                        </button>
                    @endif
                    <button wire:click="close" class="ml-auto rounded p-1 text-yt-faint hover:bg-yt-hover hover:text-yt-text">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex flex-col gap-0 md:flex-row">
                    {{-- Основная колонка --}}
                    <div class="min-w-0 flex-1 p-5">
                        <input type="text" wire:model="titleDraft" wire:change="saveTitle"
                               class="w-full border-0 bg-transparent p-0 text-lg font-semibold text-yt-text focus:ring-0">

                        {{-- Описание --}}
                        <div class="mt-4">
                            @if ($editingDescription)
                                <textarea wire:model="descriptionDraft" rows="6" class="yt-input" autofocus></textarea>
                                <div class="mt-2 flex gap-2">
                                    <button wire:click="saveDescription" class="yt-btn-primary">Сохранить</button>
                                    <button wire:click="$set('editingDescription', false)" class="yt-btn-secondary">Отмена</button>
                                </div>
                            @else
                                <div wire:click="$set('editingDescription', true)"
                                     class="min-h-[48px] cursor-text whitespace-pre-wrap rounded border border-transparent p-2 -mx-2 text-sm {{ $task->description ? 'text-yt-text' : 'text-yt-faint' }} hover:border-yt-border-soft hover:bg-yt-surface/50">{{ $task->description ?: 'Добавить описание…' }}</div>
                            @endif
                        </div>

                        {{-- Подзадачи --}}
                        @if ($task->children->isNotEmpty())
                            <div class="mt-5">
                                <div class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-yt-faint">{{ $task->type === App\Enums\TaskType::Epic ? 'Истории' : 'Задачи' }} ({{ $task->children->count() }})</div>
                                <ul class="divide-y divide-yt-border-soft rounded border border-yt-border-soft">
                                    @foreach ($task->children as $child)
                                        <li>
                                            <button wire:click="open({{ $child->id }})"
                                                    class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-yt-hover">
                                                <span class="text-xs text-yt-faint {{ $child->status->is_final ? 'line-through' : '' }}">{{ $child->full_number }}</span>
                                                <span class="truncate {{ $child->status->is_final ? 'text-yt-muted line-through' : '' }}">{{ $child->title }}</span>
                                                <span class="yt-chip ml-auto shrink-0">{{ $child->status->name }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Учёт времени --}}
                        <div class="mt-6">
                            <div class="mb-1.5 flex items-baseline gap-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-yt-faint">Затраченное время</span>
                                @if ($totalMinutes > 0)
                                    <span class="text-xs text-yt-muted">всего {{ intdiv($totalMinutes, 60) }} ч {{ $totalMinutes % 60 }} м</span>
                                @endif
                            </div>

                            <form wire:submit="addTimeLog" class="flex flex-wrap items-start gap-2 rounded border border-yt-border-soft bg-yt-surface/50 p-2">
                                <input type="number" step="0.25" min="0" wire:model="timeValue" placeholder="1.5" class="yt-input w-20">
                                <select wire:model="timeUnit" class="yt-input w-24">
                                    <option value="hours">часов</option>
                                    <option value="minutes">минут</option>
                                </select>
                                <input type="date" wire:model="timeDate" class="yt-input w-36">
                                <input type="text" wire:model="timeDescription" placeholder="Что делали? (необязательно)" class="yt-input min-w-32 flex-1">
                                <button type="submit" class="yt-btn-secondary shrink-0">Записать</button>
                                @error('timeValue') <p class="w-full text-xs text-yt-danger">{{ $message }}</p> @enderror
                                @error('timeDate') <p class="w-full text-xs text-yt-danger">{{ $message }}</p> @enderror
                            </form>

                            @if ($timeLogs->isNotEmpty())
                                <ul class="mt-2 divide-y divide-yt-border-soft">
                                    @foreach ($timeLogs as $timeLog)
                                        <li class="flex items-center gap-2 py-1.5 text-sm" wire:key="tl-{{ $timeLog->id }}">
                                            <span class="w-16 shrink-0 font-medium text-yt-accent-hover">{{ $timeLog->formatted_duration }}</span>
                                            <span class="w-20 shrink-0 text-xs text-yt-faint">{{ $timeLog->logged_date->format('d.m.Y') }}</span>
                                            <span class="shrink-0 text-xs text-yt-muted">{{ $timeLog->user->name }}</span>
                                            <span class="min-w-0 flex-1 truncate text-yt-muted">{{ $timeLog->description }}</span>
                                            @if ($timeLog->user_id === auth()->id())
                                                <button wire:click="deleteTimeLog({{ $timeLog->id }})" class="shrink-0 text-yt-faint hover:text-yt-danger" title="Удалить запись">&times;</button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        {{-- Лента активности --}}
                        <div class="mt-6">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-yt-faint">Активность</div>
                            <ul class="space-y-3">
                                @forelse ($feed as $entry)
                                    @if ($entry['kind'] === 'comment')
                                        <li class="flex gap-2.5" wire:key="feed-c-{{ $entry['item']->id }}">
                                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-yt-accent/70 text-[10px] font-semibold text-white">{{ $entry['item']->user->initials() }}</span>
                                            <div class="min-w-0 flex-1 rounded border border-yt-border-soft bg-yt-surface/50 px-3 py-2">
                                                <div class="flex items-baseline gap-2 text-xs">
                                                    <span class="font-medium text-yt-text">{{ $entry['item']->user->name }}</span>
                                                    <span class="text-yt-faint">{{ $entry['at']->format('d.m.Y H:i') }}</span>
                                                </div>
                                                <div class="mt-1 whitespace-pre-wrap text-sm text-yt-text">{{ $entry['item']->body }}</div>
                                            </div>
                                        </li>
                                    @else
                                        <li class="flex items-center gap-2.5 text-xs text-yt-muted" wire:key="feed-s-{{ $entry['item']->id }}">
                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-yt-panel ring-1 ring-yt-border">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" /></svg>
                                            </span>
                                            <span>
                                                <span class="font-medium text-yt-text">{{ $entry['item']->user->name }}</span>
                                                @if ($entry['item']->fromStatus)
                                                    перевёл(а) из «{{ $entry['item']->fromStatus->name }}» в «{{ $entry['item']->toStatus?->name ?? '—' }}»
                                                @else
                                                    установил(а) статус «{{ $entry['item']->toStatus?->name ?? '—' }}»
                                                @endif
                                            </span>
                                            <span class="text-yt-faint">{{ $entry['at']->format('d.m.Y H:i') }}</span>
                                        </li>
                                    @endif
                                @empty
                                    <li class="text-sm text-yt-faint">Пока нет активности.</li>
                                @endforelse
                            </ul>

                            <form wire:submit="addComment" class="mt-3">
                                <textarea wire:model="commentBody" rows="2" class="yt-input" placeholder="Написать комментарий…"></textarea>
                                @error('commentBody') <p class="mt-1 text-xs text-yt-danger">{{ $message }}</p> @enderror
                                <div class="mt-2 flex justify-end">
                                    <button type="submit" class="yt-btn-primary" wire:loading.attr="disabled">Отправить</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Правая колонка: метаданные --}}
                    <div class="w-full shrink-0 space-y-4 border-t border-yt-border-soft bg-yt-surface/40 p-5 md:w-64 md:border-l md:border-t-0">
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-yt-faint">Статус</label>
                            <select class="yt-input" wire:change="setStatus($event.target.value)">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->id }}" @selected($status->id === $task->status_id)>{{ $status->name }}{{ $status->is_final ? ' ✓' : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-yt-faint">Исполнитель</label>
                            <select class="yt-input" wire:change="setAssignee($event.target.value)">
                                <option value="">Не назначен</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}" @selected($member->id === $task->assignee_id)>{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-yt-faint">Приоритет</label>
                            <select class="yt-input" wire:change="setPriority($event.target.value)"
                                    style="border-left: 3px solid {{ $task->priority->color() }}">
                                @foreach ($priorities as $priorityCase)
                                    <option value="{{ $priorityCase->value }}" @selected($priorityCase === $task->priority)>{{ $priorityCase->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-yt-faint">Срок</label>
                            <input type="date" value="{{ $task->due_date?->toDateString() }}"
                                   wire:change="setDueDate($event.target.value)" class="yt-input">
                        </div>

                        {{-- Зависимости --}}
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-yt-faint">Зависит от</label>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($task->dependsOn as $dep)
                                    <span class="yt-chip {{ $dep->status->is_final ? 'opacity-60' : 'border-yt-danger/50 text-yt-text' }}" wire:key="dep-{{ $dep->id }}">
                                        <button wire:click="open({{ $dep->id }})" class="hover:underline {{ $dep->status->is_final ? 'line-through' : '' }}">{{ $dep->full_number }}</button>
                                        <button wire:click="removeDependency({{ $dep->id }})" class="text-yt-faint hover:text-yt-danger" title="Убрать зависимость">&times;</button>
                                    </span>
                                @empty
                                    <span class="text-xs text-yt-faint">нет</span>
                                @endforelse
                            </div>
                            <div class="relative mt-2">
                                <input type="text" wire:model.live.debounce.300ms="depQuery" placeholder="Найти задачу…" class="yt-input text-xs">
                                @if ($depOptions->isNotEmpty())
                                    <div class="absolute z-30 mt-1 w-full rounded border border-yt-border bg-yt-panel py-1 shadow-modal">
                                        @foreach ($depOptions as $option)
                                            <button wire:click="addDependency({{ $option->id }})"
                                                    class="block w-full truncate px-2 py-1 text-left text-xs hover:bg-yt-hover">
                                                {{ $option->full_number }} · {{ $option->title }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @if ($task->dependents->isNotEmpty())
                                <div class="mt-2 text-xs text-yt-faint">
                                    Блокирует:
                                    @foreach ($task->dependents as $dependent)
                                        <button wire:click="open({{ $dependent->id }})" class="text-yt-muted hover:underline">{{ $dependent->full_number }}</button>@if(!$loop->last), @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="border-t border-yt-border-soft pt-3 text-xs text-yt-faint">
                            <div>Проект: {{ $project->name }}</div>
                            <div class="mt-1">Создал(а) {{ $task->creator->name }}</div>
                            <div>{{ $task->created_at->format('d.m.Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
