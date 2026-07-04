<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-4 pt-16"
             wire:click.self="$set('show', false)"
             x-data x-on:keydown.escape.window="$wire.set('show', false)">
            <div class="w-full max-w-lg rounded-lg border border-yt-border bg-yt-panel shadow-modal">
                <div class="flex items-center justify-between border-b border-yt-border-soft px-5 py-3">
                    <span class="text-sm font-semibold">Новая задача в {{ $project->name }}</span>
                    <button wire:click="$set('show', false)" class="text-yt-faint hover:text-yt-text">&times;</button>
                </div>

                <form wire:submit="create" class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-yt-muted">Тип</label>
                        <div class="flex gap-1">
                            @foreach (['epic' => 'Эпик', 'story' => 'История', 'task' => 'Задача'] as $typeValue => $typeLabel)
                                <button type="button" wire:click="$set('type', '{{ $typeValue }}')"
                                        class="rounded px-3 py-1.5 text-sm {{ $type === $typeValue ? 'bg-yt-accent text-white' : 'border border-yt-border text-yt-muted hover:bg-yt-hover' }}">
                                    {{ $typeLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-yt-muted">Название</label>
                        <input type="text" wire:model="title" class="yt-input" placeholder="Что нужно сделать?">
                        @error('title') <p class="mt-1 text-xs text-yt-danger">{{ $message }}</p> @enderror
                    </div>

                    @if ($type !== 'epic')
                        <div>
                            <label class="mb-1 block text-xs font-medium text-yt-muted">{{ $parentLabel }} (родитель)</label>
                            <select wire:model="parentId" class="yt-input">
                                <option value="">— {{ $type === 'story' ? 'Без эпика' : 'Без истории' }} —</option>
                                @foreach ($parentOptions as $option)
                                    <option value="{{ $option->id }}">{{ $option->full_number }} · {{ $option->title }}</option>
                                @endforeach
                            </select>
                            @error('parentId') <p class="mt-1 text-xs text-yt-danger">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-xs font-medium text-yt-muted">Описание</label>
                        <textarea wire:model="description" rows="4" class="yt-input" placeholder="Необязательно"></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-yt-muted">Приоритет</label>
                            <select wire:model="priority" class="yt-input">
                                @foreach ($priorities as $priorityCase)
                                    <option value="{{ $priorityCase->value }}">{{ $priorityCase->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-yt-muted">Исполнитель</label>
                            <select wire:model="assigneeId" class="yt-input">
                                <option value="">Не назначен</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-yt-muted">Срок</label>
                            <input type="date" wire:model="dueDate" class="yt-input">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" wire:click="$set('show', false)" class="yt-btn-secondary">Отмена</button>
                        <button type="submit" class="yt-btn-primary" wire:loading.attr="disabled">Создать</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
