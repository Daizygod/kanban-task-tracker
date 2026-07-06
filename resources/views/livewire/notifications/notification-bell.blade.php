<div wire:poll.30s>
    <button wire:click="toggle"
            class="flex h-10 w-full items-center gap-3 rounded-md px-3 text-sm {{ $open ? 'bg-[rgba(81,95,104,0.5)] text-yt-text' : ($unreadCount > 0 ? 'text-yt-text hover:bg-[rgba(81,95,104,0.3)]' : 'text-yt-muted hover:bg-[rgba(81,95,104,0.3)] hover:text-yt-text') }}">
        <span class="relative shrink-0">
            <svg class="h-5 w-5 {{ $unreadCount > 0 ? 'text-yt-danger' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            @if ($unreadCount > 0)
                <span class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-yt-danger ring-2 ring-yt-bg"></span>
            @endif
        </span>
        Уведомления
        @if ($unreadCount > 0)
            <span class="ml-auto rounded-full bg-yt-danger px-1.5 py-px text-[11px] font-semibold text-white">{{ $unreadCount }}</span>
        @endif
    </button>

    @if ($open)
        {{-- Панель-флайаут справа от сайдбара --}}
        <div class="fixed inset-0 z-[80]" wire:click="toggle"></div>
        <div class="fixed bottom-4 left-[204px] z-[90] flex max-h-[70vh] w-96 flex-col rounded-lg border border-yt-border bg-yt-panel shadow-modal">
            <div class="flex items-center justify-between border-b border-yt-border-soft px-4 py-2.5">
                <span class="text-sm font-semibold">Уведомления</span>
                @if ($unreadCount > 0)
                    <button wire:click="markAllRead" class="text-xs text-yt-link hover:underline">Отметить все прочитанными</button>
                @endif
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                @forelse ($notifications as $notification)
                    <button wire:click="openNotification({{ $notification->id }})"
                            wire:key="ntf-{{ $notification->id }}"
                            class="flex w-full items-start gap-2.5 border-b border-yt-border-soft px-4 py-2.5 text-left hover:bg-yt-hover {{ $notification->read_at ? 'opacity-60' : '' }}">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-transparent' : 'bg-yt-danger' }}"></span>
                        <span class="min-w-0">
                            <span class="block text-sm leading-snug">
                                <span class="text-yt-text">{{ $notification->actor->name }}</span>
                                <span class="text-yt-muted">{{ $notification->type === App\Models\UserNotification::TYPE_ASSIGNED ? 'назначил(а) вас исполнителем' : 'упомянул(а) вас в' }}</span>
                                <span class="text-yt-link">{{ $notification->task->full_number }}</span>
                            </span>
                            <span class="mt-0.5 block truncate text-xs text-yt-muted">{{ $notification->task->title }}</span>
                            @if ($notification->context)
                                <span class="mt-0.5 block truncate text-xs italic text-yt-faint">«{{ $notification->context }}»</span>
                            @endif
                            <span class="mt-0.5 block text-[11px] text-yt-faint">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-yt-faint">Уведомлений пока нет.</div>
                @endforelse
            </div>
        </div>
    @endif
</div>
