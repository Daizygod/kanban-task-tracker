<div>
    <x-slot name="header">
        <div class="flex h-12 items-center gap-2 px-6 text-sm">
            <a href="{{ route('projects.board', $project) }}" class="text-yt-muted hover:text-yt-link">{{ $project->name }}</a>
            <span class="text-yt-muted">/</span>
            <h1 class="text-yt-text">Настройки</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-8 p-6">
        {{-- Общие --}}
        <section class="rounded-lg border border-yt-border-soft bg-yt-panel">
            <div class="border-b border-yt-border-soft px-5 py-3 text-sm font-semibold">Общие</div>
            <form wire:submit="saveGeneral" class="space-y-4 p-5">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-yt-muted">Название</label>
                        <input type="text" wire:model="name" class="yt-input">
                        @error('name') <p class="mt-1 text-xs text-yt-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-yt-muted">Ключ</label>
                        <input type="text" value="{{ $project->key }}" class="yt-input w-24 opacity-60" disabled title="Ключ проекта нельзя изменить — на него завязаны номера задач">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-yt-muted">Описание</label>
                    <textarea wire:model="description" rows="3" class="yt-input"></textarea>
                    @error('description') <p class="mt-1 text-xs text-yt-danger">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="yt-btn-primary">Сохранить</button>
                </div>
            </form>
        </section>

        {{-- Статусы --}}
        <section class="rounded-lg border border-yt-border-soft bg-yt-panel">
            <div class="border-b border-yt-border-soft px-5 py-3">
                <span class="text-sm font-semibold">Статусы (колонки досок)</span>
                <p class="mt-0.5 text-xs text-yt-faint">Финальный статус означает «завершено»: задачу нельзя перевести в него, пока не закрыты её зависимости. Финальный статус всегда ровно один — пометка другого статуса переносит флаг.</p>
            </div>
            <ul class="divide-y divide-yt-border-soft">
                @foreach ($statuses as $index => $status)
                    <li class="flex items-center gap-3 px-5 py-2.5" wire:key="status-{{ $status->id }}">
                        <div class="flex flex-col">
                            <button wire:click="moveStatus({{ $status->id }}, 'up')" @disabled($index === 0)
                                    class="text-yt-faint hover:text-yt-text disabled:opacity-30" title="Выше">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                            </button>
                            <button wire:click="moveStatus({{ $status->id }}, 'down')" @disabled($index === $statuses->count() - 1)
                                    class="text-yt-faint hover:text-yt-text disabled:opacity-30" title="Ниже">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                        </div>
                        <input type="text" value="{{ $status->name }}"
                               wire:change="renameStatus({{ $status->id }}, $event.target.value)"
                               class="yt-input max-w-xs flex-1">
                        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-yt-muted">
                            <input type="radio" name="final-status" @checked($status->is_final)
                                   wire:click="makeFinal({{ $status->id }})"
                                   class="border-yt-border bg-yt-surface text-yt-accent focus:ring-yt-accent">
                            финальный
                        </label>
                        <span class="ml-auto text-xs text-yt-faint">{{ $status->tasks_count }} задач</span>
                        <button wire:click="deleteStatus({{ $status->id }})"
                                wire:confirm="Удалить статус «{{ $status->name }}»?"
                                class="rounded p-1 text-yt-faint hover:bg-yt-hover hover:text-yt-danger" title="Удалить">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </li>
                @endforeach
            </ul>
            <form wire:submit="addStatus" class="flex items-center gap-3 border-t border-yt-border-soft px-5 py-3">
                <input type="text" wire:model="newStatusName" placeholder="Новый статус" class="yt-input max-w-xs flex-1">
                <label class="flex cursor-pointer items-center gap-1.5 text-xs text-yt-muted">
                    <input type="checkbox" wire:model="newStatusFinal"
                           class="rounded border-yt-border bg-yt-surface text-yt-accent focus:ring-yt-accent">
                    финальный
                </label>
                <button type="submit" class="yt-btn-secondary ml-auto">Добавить</button>
            </form>
            @error('newStatusName') <p class="px-5 pb-3 text-xs text-yt-danger">{{ $message }}</p> @enderror
        </section>

        {{-- Приоритеты --}}
        <section class="rounded-lg border border-yt-border-soft bg-yt-panel">
            <div class="border-b border-yt-border-soft px-5 py-3">
                <span class="text-sm font-semibold">Приоритеты</span>
                <p class="mt-0.5 text-xs text-yt-faint">Порядок задаёт сортировку карточек в колонке (нижний в списке — самый важный). Приоритет «по умолчанию» присваивается новым задачам, он всегда ровно один; его и последний оставшийся приоритет удалить нельзя. При удалении приоритета его задачи получают приоритет по умолчанию.</p>
            </div>
            <ul class="divide-y divide-yt-border-soft">
                @foreach ($priorities as $index => $priority)
                    <li class="flex items-center gap-3 px-5 py-2.5" wire:key="priority-{{ $priority->id }}">
                        <div class="flex flex-col">
                            <button wire:click="movePriority({{ $priority->id }}, 'up')" @disabled($index === 0)
                                    class="text-yt-faint hover:text-yt-text disabled:opacity-30" title="Выше">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                            </button>
                            <button wire:click="movePriority({{ $priority->id }}, 'down')" @disabled($index === $priorities->count() - 1)
                                    class="text-yt-faint hover:text-yt-text disabled:opacity-30" title="Ниже">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                        </div>
                        <input type="color" value="{{ $priority->color }}"
                               wire:change="setPriorityColor({{ $priority->id }}, $event.target.value)"
                               class="h-7 w-9 cursor-pointer rounded border border-yt-border bg-transparent p-0.5"
                               title="Цвет приоритета">
                        <input type="text" value="{{ $priority->name }}"
                               wire:change="renamePriority({{ $priority->id }}, $event.target.value)"
                               class="yt-input max-w-xs flex-1">
                        <label class="flex cursor-pointer items-center gap-1.5 text-xs text-yt-muted">
                            <input type="radio" name="default-priority" @checked($priority->is_default)
                                   wire:click="makeDefaultPriority({{ $priority->id }})"
                                   class="border-yt-border bg-yt-surface text-yt-accent focus:ring-yt-accent">
                            по умолчанию
                        </label>
                        <span class="ml-auto text-xs text-yt-faint">{{ $priority->tasks_count }} задач</span>
                        <button wire:click="deletePriority({{ $priority->id }})"
                                wire:confirm="Удалить приоритет «{{ $priority->name }}»? Его задачи получат приоритет по умолчанию."
                                class="rounded p-1 text-yt-faint hover:bg-yt-hover hover:text-yt-danger" title="Удалить">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </li>
                @endforeach
            </ul>
            <form wire:submit="addPriority" class="flex items-center gap-3 border-t border-yt-border-soft px-5 py-3">
                <input type="color" wire:model="newPriorityColor"
                       class="h-7 w-9 cursor-pointer rounded border border-yt-border bg-transparent p-0.5" title="Цвет">
                <input type="text" wire:model="newPriorityName" placeholder="Новый приоритет" class="yt-input max-w-xs flex-1">
                <button type="submit" class="yt-btn-secondary ml-auto">Добавить</button>
            </form>
            @error('newPriorityName') <p class="px-5 pb-3 text-xs text-yt-danger">{{ $message }}</p> @enderror
            @error('newPriorityColor') <p class="px-5 pb-3 text-xs text-yt-danger">{{ $message }}</p> @enderror
        </section>

        {{-- Участники --}}
        <section class="rounded-lg border border-yt-border-soft bg-yt-panel">
            <div class="border-b border-yt-border-soft px-5 py-3 text-sm font-semibold">Участники</div>
            <ul class="divide-y divide-yt-border-soft">
                @foreach ($members as $member)
                    <li class="flex items-center gap-3 px-5 py-2.5" wire:key="member-{{ $member->id }}">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-yt-accent/80 text-xs font-semibold text-white">{{ $member->initials() }}</span>
                        <div class="min-w-0">
                            <div class="truncate text-sm">{{ $member->name }}</div>
                            <div class="truncate text-xs text-yt-faint">{{ $member->email }}</div>
                        </div>
                        @if ($member->id === $project->owner_id)
                            <span class="yt-chip">создатель</span>
                        @endif
                        <button wire:click="removeMember({{ $member->id }})"
                                wire:confirm="Убрать {{ $member->name }} из проекта?"
                                class="ml-auto rounded p-1 text-yt-faint hover:bg-yt-hover hover:text-yt-danger" title="Убрать из проекта">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </li>
                @endforeach
            </ul>
            <form wire:submit="addMember" class="border-t border-yt-border-soft px-5 py-3">
                <div class="flex items-center gap-3">
                    <input type="email" wire:model="memberEmail" placeholder="email зарегистрированного пользователя" class="yt-input max-w-xs flex-1">
                    <button type="submit" class="yt-btn-secondary">Добавить участника</button>
                </div>
                @error('memberEmail') <p class="mt-2 text-xs text-yt-danger">{{ $message }}</p> @enderror
            </form>
        </section>
    </div>
</div>
