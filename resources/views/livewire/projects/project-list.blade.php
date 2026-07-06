<div>
    <x-slot name="header">
        <div class="flex h-12 items-center px-6">
        <h1 class="text-base font-semibold">Проекты</h1>
        {{-- header-слот живёт вне DOM Livewire-компонента: wire:click тут не работает, только dispatch --}}
        <button x-data x-on:click="Livewire.dispatch('project-create-open')" class="yt-btn-primary ml-auto">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Новый проект
        </button>
        </div>
    </x-slot>

    <div class="p-6">
        @if ($projects->isEmpty())
            <div class="mx-auto mt-16 max-w-md text-center">
                <div class="text-8xl">😢</div>
                <p class="mt-6 text-lg font-medium">Нет проектов</p>
                <p class="mt-2 text-sm text-yt-muted">Создайте первый проект — статусы «Открыта», «В работе» и «Завершена» появятся автоматически, потом их можно настроить.</p>
                <button wire:click="$set('showCreateModal', true)" class="yt-btn-primary mt-6">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Создать проект
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($projects as $project)
                    <a href="{{ route('projects.board', $project) }}"
                       class="group flex flex-col rounded-lg border border-yt-border-soft bg-yt-panel p-4 shadow-card transition-colors hover:border-yt-border hover:bg-yt-card">
                        <div class="flex items-start gap-3">
                            <span class="rounded bg-yt-accent/15 px-2 py-1 text-xs font-bold tracking-wider text-yt-accent-hover">{{ $project->key }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium">{{ $project->name }}</div>
                                <div class="mt-0.5 text-xs text-yt-faint">создал {{ $project->owner->name }}</div>
                            </div>
                            <span onclick="event.preventDefault(); window.location='{{ route('projects.settings', $project) }}'"
                                  class="rounded p-1 text-yt-faint opacity-0 transition-opacity hover:bg-yt-hover hover:text-yt-text group-hover:opacity-100"
                                  title="Настройки проекта">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            </span>
                        </div>
                        @if ($project->description)
                            <p class="mt-3 line-clamp-2 text-sm text-yt-muted">{{ $project->description }}</p>
                        @endif
                        <div class="mt-auto flex gap-4 pt-4 text-xs text-yt-faint">
                            <span>{{ $project->tasks_count }} задач</span>
                            <span>{{ $project->members_count }} участников</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Модалка создания проекта --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 pt-24"
             wire:click.self="$set('showCreateModal', false)"
             x-data x-on:keydown.escape.window="$wire.set('showCreateModal', false)">
            <div class="w-full max-w-md rounded-lg border border-yt-border bg-yt-panel shadow-modal">
                <div class="border-b border-yt-border-soft px-5 py-3 text-sm font-semibold">Новый проект</div>
                <form wire:submit="createProject" class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-yt-muted">Название</label>
                        <input type="text" wire:model="name" class="yt-input" placeholder="Разработка портала" autofocus>
                        @error('name') <p class="mt-1 text-xs text-yt-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-yt-muted">Ключ (3 латинские буквы)</label>
                        <input type="text" wire:model="key" maxlength="3" class="yt-input w-28 uppercase" placeholder="POR">
                        <p class="mt-1 text-xs text-yt-faint">Номера задач будут вида {{ $key ?: 'POR' }}-1, {{ $key ?: 'POR' }}-2…</p>
                        @error('key') <p class="mt-1 text-xs text-yt-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-yt-muted">Описание</label>
                        <textarea wire:model="description" rows="3" class="yt-input" placeholder="Необязательно"></textarea>
                        @error('description') <p class="mt-1 text-xs text-yt-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="yt-btn-secondary">Отмена</button>
                        <button type="submit" class="yt-btn-primary" wire:loading.attr="disabled">Создать</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
