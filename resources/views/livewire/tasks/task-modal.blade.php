<div>
    @if ($show && $task)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 pt-10"
             wire:click.self="close"
             x-data x-on:keydown.escape.window="$wire.close()">
            <div class="flex w-full max-w-[1032px] flex-col rounded-lg border border-yt-border bg-yt-bg shadow-modal">
                <div class="flex flex-col md:flex-row">
                    {{-- Основная колонка --}}
                    <div class="min-w-0 flex-1 px-8 py-5">
                        {{-- Шапка: номер + кто создал/обновил --}}
                        <div class="flex items-start gap-2 text-sm">
                            <span class="font-medium {{ $task->status->is_final ? 'text-yt-faint line-through' : 'text-yt-muted' }}">{{ $task->full_number }}</span>
                            <span class="yt-chip">{{ $task->type->label() }}</span>
                            <div class="min-w-0 text-[13px] leading-5 text-yt-muted">
                                Создал(а) <span class="text-yt-text">{{ $task->creator->name }}</span> {{ $task->created_at->diffForHumans() }}
                                @if ($task->updated_at->ne($task->created_at))
                                    <br>Обновлено {{ $task->updated_at->diffForHumans() }}
                                @endif
                            </div>
                            @if ($task->parent)
                                <button wire:click="open({{ $task->parent->id }})" class="ml-auto shrink-0 text-[13px] text-yt-link hover:underline" title="Родитель">
                                    ↑ {{ $task->parent->full_number }}
                                </button>
                            @endif
                        </div>

                        <input type="text" wire:model="titleDraft" wire:change="saveTitle"
                               class="my-1.5 w-full border-0 bg-transparent p-0 text-2xl font-semibold leading-7 text-yt-text focus:ring-0">

                        {{-- Описание --}}
                        @if ($editingDescription)
                            <x-mention-textarea model="descriptionDraft" rows="6" placeholder="Опишите задачу… @ — упомянуть" />
                            <div class="mt-2 flex gap-2">
                                <button wire:click="saveDescription" class="yt-btn-primary">Сохранить</button>
                                <button wire:click="$set('editingDescription', false)" class="yt-btn-secondary">Отмена</button>
                            </div>
                        @else
                            <div wire:click="$set('editingDescription', true)"
                                 class="-mx-2 min-h-[36px] cursor-text whitespace-pre-wrap rounded px-2 py-1 text-sm leading-relaxed {{ $task->description ? 'text-yt-text' : 'text-yt-faint' }} hover:bg-yt-panel/40">{!! $task->description ? App\Support\RichText::render($task->description, $project) : 'Добавить описание…' !!}</div>
                        @endif

                        {{-- Связи: зависит от / блокирует / подзадачи --}}
                        @foreach ([
                            ['label' => 'Зависит от', 'items' => $task->dependsOn, 'removable' => true, 'dotFor' => 'dep'],
                            ['label' => 'Блокирует', 'items' => $task->dependents, 'removable' => false, 'dotFor' => 'dependent'],
                            ['label' => $task->type === App\Enums\TaskType::Epic ? 'Истории' : 'Подзадачи', 'items' => $task->children, 'removable' => false, 'dotFor' => 'child'],
                        ] as $section)
                            @if ($section['items']->isNotEmpty())
                                <div class="mt-5 border-t border-yt-border-soft pt-3">
                                    <div class="mb-2 flex items-center gap-1.5 text-sm text-yt-text">
                                        <svg class="h-3 w-3 text-yt-muted" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                        {{ $section['label'] }} <span class="text-yt-muted">{{ $section['items']->count() }}</span>
                                    </div>
                                    <ul class="space-y-1.5 pl-1">
                                        @foreach ($section['items'] as $item)
                                            <li class="group flex items-center gap-2 text-sm" wire:key="{{ $section['dotFor'] }}-{{ $item->id }}">
                                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-sm text-[9px] font-bold text-white"
                                                      style="background: {{ $item->status->is_final ? '#59a869' : ($section['dotFor'] === 'dep' ? '#e44899' : '#366acf') }}">
                                                    {{ mb_substr($item->status->name, 0, 1) }}
                                                </span>
                                                <button wire:click="open({{ $item->id }})" class="shrink-0 text-yt-link hover:underline {{ $item->status->is_final ? 'line-through opacity-70' : '' }}">{{ $item->full_number }}</button>
                                                <span class="truncate {{ $item->status->is_final ? 'text-yt-muted line-through' : 'text-yt-text' }}">{{ $item->title }}</span>
                                                <span class="ml-auto shrink-0 text-xs text-yt-faint">{{ $item->status->name }}</span>
                                                @if ($section['removable'])
                                                    <button wire:click="removeDependency({{ $item->id }})"
                                                            class="shrink-0 rounded p-0.5 text-yt-faint opacity-0 hover:text-yt-danger group-hover:opacity-100" title="Убрать зависимость">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                                    </button>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endforeach

                        {{-- Затраченное время --}}
                        <div class="mt-5 border-t border-yt-border-soft pt-3">
                            <div class="mb-2 flex items-baseline gap-2 text-sm text-yt-text">
                                Затраченное время
                                @if ($totalMinutes > 0)
                                    <span class="text-yt-muted">{{ intdiv($totalMinutes, 60) }} ч {{ $totalMinutes % 60 }} м</span>
                                @endif
                            </div>

                            <form wire:submit="addTimeLog" class="flex flex-wrap items-start gap-2">
                                <input type="number" step="0.25" min="0" wire:model="timeValue" placeholder="1.5" class="yt-input w-20">
                                <select wire:model="timeUnit" class="yt-input w-24">
                                    <option value="hours">часов</option>
                                    <option value="minutes">минут</option>
                                </select>
                                <select wire:model="timeWorkTypeId" class="yt-input w-40 {{ $errors->has('timeWorkTypeId') ? '!border-yt-danger' : '' }}">
                                    <option value="">Тип работы…</option>
                                    @foreach ($workTypes as $workType)
                                        <option value="{{ $workType->id }}">{{ $workType->name }}</option>
                                    @endforeach
                                </select>
                                <input type="date" wire:model="timeDate" class="yt-input w-36">
                                <input type="text" wire:model="timeDescription" placeholder="Что делали? (необязательно)" class="yt-input min-w-32 flex-1">
                                <button type="submit" class="yt-btn-secondary shrink-0">Записать</button>
                                @error('timeValue') <p class="w-full text-xs text-yt-danger">{{ $message }}</p> @enderror
                                @error('timeWorkTypeId') <p class="w-full text-xs text-yt-danger">{{ $message }}</p> @enderror
                                @error('timeDate') <p class="w-full text-xs text-yt-danger">{{ $message }}</p> @enderror
                            </form>

                            @if ($timeLogs->isNotEmpty())
                                <ul class="mt-2">
                                    @foreach ($timeLogs as $timeLog)
                                        <li class="group flex items-center gap-3 rounded px-1 py-1 text-sm hover:bg-yt-panel/40" wire:key="tl-{{ $timeLog->id }}">
                                            <span class="w-16 shrink-0 text-yt-link">{{ $timeLog->formatted_duration }}</span>
                                            <span class="w-20 shrink-0 text-xs text-yt-faint">{{ $timeLog->logged_date->format('d.m.Y') }}</span>
                                            <span class="shrink-0 rounded-full px-2 py-px text-[11px] font-medium"
                                                  style="background: {{ $timeLog->workType->color }}26; color: {{ $timeLog->workType->color }}">{{ $timeLog->workType->name }}</span>
                                            <span class="shrink-0 text-xs text-yt-muted">{{ $timeLog->user->name }}</span>
                                            <span class="min-w-0 flex-1 truncate text-yt-muted">{{ $timeLog->description }}</span>
                                            @if ($timeLog->user_id === auth()->id())
                                                <button wire:click="deleteTimeLog({{ $timeLog->id }})" class="shrink-0 text-yt-faint opacity-0 hover:text-yt-danger group-hover:opacity-100" title="Удалить запись">&times;</button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        {{-- Активность --}}
                        <div class="mt-5 border-t border-yt-border-soft pt-3">
                            <div class="mb-3 flex items-center gap-1">
                                <button wire:click="$toggle('showComments')"
                                        class="rounded p-1.5 {{ $showComments ? 'bg-yt-selected/60 text-yt-link' : 'text-yt-muted hover:bg-yt-panel' }}"
                                        title="Комментарии">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                                </button>
                                <button wire:click="$toggle('showHistory')"
                                        class="rounded p-1.5 {{ $showHistory ? 'bg-yt-selected/60 text-yt-link' : 'text-yt-muted hover:bg-yt-panel' }}"
                                        title="История статусов">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                </button>
                                <span class="ml-2 text-[13px] text-yt-muted">Активность</span>
                            </div>

                            <ul class="space-y-4">
                                @forelse ($feed as $entry)
                                    @if ($entry['kind'] === 'comment')
                                        <li class="flex gap-2.5" wire:key="feed-c-{{ $entry['item']->id }}">
                                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-yt-accent text-[10px] font-semibold text-white">{{ $entry['item']->user->initials() }}</span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 text-sm">
                                                    <span class="text-yt-text">{{ $entry['item']->user->name }}</span>
                                                    <span class="h-1 w-1 rounded-full bg-yt-faint"></span>
                                                    <span class="text-[13px] text-yt-muted">Прокомментировал(а) {{ $entry['at']->diffForHumans() }}</span>
                                                </div>
                                                <div class="mt-1.5 whitespace-pre-wrap text-sm leading-relaxed text-yt-text">{!! App\Support\RichText::render($entry['item']->body, $project) !!}</div>
                                            </div>
                                        </li>
                                    @elseif ($entry['kind'] === 'activity')
                                        <li class="flex items-center gap-2.5" wire:key="feed-a-{{ $entry['item']->id }}">
                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-yt-panel">
                                                @if ($entry['item']->field === 'created')
                                                    <svg class="h-3 w-3 text-yt-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                                @else
                                                    <svg class="h-3 w-3 text-yt-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z" /></svg>
                                                @endif
                                            </span>
                                            <span class="min-w-0 text-[13px] text-yt-muted">
                                                <span class="text-yt-text">{{ $entry['item']->user?->name ?? 'Система' }}</span>
                                                <span class="mx-1 text-yt-faint">·</span>
                                                @if ($entry['item']->field === 'created')
                                                    создал(а) {{ match ($task->type) {
                                                        App\Enums\TaskType::Epic => 'эпик',
                                                        App\Enums\TaskType::Story => 'историю',
                                                        App\Enums\TaskType::Task => 'задачу',
                                                    } }}
                                                @elseif ($entry['item']->field === 'description')
                                                    изменил(а) описание
                                                @else
                                                    {{ $entry['item']->fieldLabel() }}:
                                                    <span class="text-yt-faint">{{ $entry['item']->old_value ?? '—' }}</span> →
                                                    <span class="text-yt-text">{{ $entry['item']->new_value ?? '—' }}</span>
                                                @endif
                                                <span class="mx-1 text-yt-faint">·</span>
                                                {{ $entry['at']->diffForHumans() }}
                                            </span>
                                        </li>
                                    @else
                                        <li class="flex items-center gap-2.5" wire:key="feed-s-{{ $entry['item']->id }}">
                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-yt-panel">
                                                <svg class="h-3 w-3 text-yt-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" /></svg>
                                            </span>
                                            <span class="min-w-0 text-[13px] text-yt-muted">
                                                <span class="text-yt-text">{{ $entry['item']->user->name }}</span>
                                                <span class="mx-1 text-yt-faint">·</span>
                                                @if ($entry['item']->fromStatus)
                                                    <span class="text-yt-faint">{{ $entry['item']->fromStatus->name }}</span> →
                                                @endif
                                                <span class="text-yt-text">{{ $entry['item']->toStatus?->name ?? '—' }}</span>
                                                <span class="mx-1 text-yt-faint">·</span>
                                                {{ $entry['at']->diffForHumans() }}
                                            </span>
                                        </li>
                                    @endif
                                @empty
                                    <li class="text-sm text-yt-faint">Пока нет активности.</li>
                                @endforelse
                            </ul>

                            <form wire:submit="addComment" class="mt-4 flex gap-2.5">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-yt-accent text-[10px] font-semibold text-white">{{ auth()->user()->initials() }}</span>
                                <div class="flex-1">
                                    <x-mention-textarea model="commentBody" rows="2" placeholder="Написать комментарий… @ — упомянуть человека или задачу" />
                                    @error('commentBody') <p class="mt-1 text-xs text-yt-danger">{{ $message }}</p> @enderror
                                    <div class="mt-2 flex justify-end">
                                        <button type="submit" class="yt-btn-primary" wire:loading.attr="disabled">Отправить</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Правая панель полей: label сверху, значение ниже, бейдж справа --}}
                    <div class="w-full shrink-0 border-t border-yt-border-soft md:w-60 md:border-l md:border-t-0">
                        <div class="flex justify-end px-3 pt-3">
                            <button wire:click="close" class="rounded p-1 text-yt-muted hover:bg-yt-panel hover:text-yt-text">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="space-y-4 px-4 pb-5 pt-1">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-xs leading-5 text-yt-muted">Проект</div>
                                    <div class="truncate text-sm text-yt-text">{{ $project->name }}</div>
                                </div>
                                <span class="mt-1 flex h-5 w-6 shrink-0 items-center justify-center rounded bg-yt-accent text-[9px] font-bold text-white">{{ mb_substr($project->key, 0, 2) }}</span>
                            </div>

                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs leading-5 text-yt-muted">Приоритет</div>
                                    <select class="yt-field-select" wire:change="setPriority($event.target.value)">
                                        @foreach ($priorities as $priorityOption)
                                            <option value="{{ $priorityOption->id }}" @selected($priorityOption->id === $task->priority_id)>{{ $priorityOption->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded text-[10px] font-bold text-white"
                                      style="background: {{ $task->priority->color }}; box-shadow: 0 0 8px {{ $task->priority->color }}99">{{ mb_substr($task->priority->name, 0, 1) }}</span>
                            </div>

                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs leading-5 text-yt-muted">Исполнитель</div>
                                    <select class="yt-field-select" wire:change="setAssignee($event.target.value)">
                                        <option value="">Не назначен</option>
                                        @foreach ($members as $member)
                                            <option value="{{ $member->id }}" @selected($member->id === $task->assignee_id)>{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if ($task->assignee)
                                    <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-yt-accent text-[8px] font-semibold text-white">{{ $task->assignee->initials() }}</span>
                                @endif
                            </div>

                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs leading-5 text-yt-muted">Статус</div>
                                    <select class="yt-field-select" wire:change="setStatus($event.target.value)">
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status->id }}" @selected($status->id === $task->status_id)>{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded text-[10px] font-bold text-white"
                                      style="background: {{ $task->status->is_final ? '#59a869' : '#366acf' }}">{{ mb_substr($task->status->name, 0, 1) }}</span>
                            </div>

                            <div>
                                <div class="text-xs leading-5 text-yt-muted">Срок <span class="text-yt-faint">(необязательно)</span></div>
                                <div class="flex items-center gap-1">
                                    <input type="date" value="{{ $task->due_date?->toDateString() }}"
                                           wire:change="setDueDate($event.target.value)"
                                           wire:key="due-{{ $task->id }}-{{ $task->due_date?->toDateString() ?? 'none' }}"
                                           class="yt-field-select">
                                    @if ($task->due_date)
                                        <button wire:click="setDueDate(null)" class="shrink-0 rounded p-0.5 text-yt-faint hover:text-yt-danger" title="Убрать срок">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if ($customBoards->isNotEmpty())
                                <div>
                                    <div class="text-xs leading-5 text-yt-muted">Видимость на досках</div>
                                    <div class="mt-1 space-y-1">
                                        <label class="flex items-center gap-2 text-sm text-yt-faint" title="Дефолтная доска показывает все задачи">
                                            <input type="checkbox" checked disabled class="rounded border-yt-border bg-yt-surface text-yt-accent opacity-50">
                                            <span class="truncate">{{ $project->boards->firstWhere('is_default', true)?->name ?? 'Все задачи' }}</span>
                                        </label>
                                        @foreach ($customBoards as $board)
                                            <label class="flex cursor-pointer items-center gap-2 text-sm text-yt-text" wire:key="board-visibility-{{ $board->id }}">
                                                <input type="checkbox" @checked($task->boards->contains($board->id))
                                                       wire:change="toggleBoard({{ $board->id }})"
                                                       class="rounded border-yt-border bg-yt-surface text-yt-accent focus:ring-yt-accent">
                                                <span class="truncate">{{ $board->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div>
                                <div class="text-xs leading-5 text-yt-muted">Добавить зависимость</div>
                                <div class="relative mt-1">
                                    <input type="text" wire:model.live.debounce.300ms="depQuery" placeholder="Номер или название…" class="yt-input px-2 py-1 text-xs">
                                    @if ($depOptions->isNotEmpty())
                                        <div class="absolute z-30 mt-1 w-full rounded-lg border border-yt-border bg-yt-panel py-1 shadow-modal">
                                            @foreach ($depOptions as $option)
                                                <button wire:click="addDependency({{ $option->id }})"
                                                        class="block w-full truncate px-2 py-1 text-left text-xs hover:bg-yt-hover">
                                                    {{ $option->full_number }} · {{ $option->title }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
