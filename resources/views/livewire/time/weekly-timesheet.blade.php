@php
    $formatMinutes = fn (int $minutes) => $minutes >= 60
        ? intdiv($minutes, 60).' ч'.($minutes % 60 ? ' '.($minutes % 60).' м' : '')
        : $minutes.' м';
@endphp

<div class="flex h-full flex-col">
    <x-slot name="header">
        <h1 class="text-base font-semibold">Учёт времени</h1>

        <div class="ml-6 flex items-center gap-2">
            <select wire:change="selectUser($event.target.value)" class="yt-input w-52 py-1">
                @foreach ($teammates as $teammate)
                    <option value="{{ $teammate->id }}" @selected($teammate->id === $viewedUserId)>
                        {{ $teammate->name }}{{ $teammate->id === auth()->id() ? ' (я)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="ml-auto flex items-center gap-1">
            <button wire:click="previousWeek" class="rounded p-1.5 text-yt-muted hover:bg-yt-hover hover:text-yt-text" title="Предыдущая неделя">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </button>
            <button wire:click="currentWeek" class="yt-btn-secondary py-1 {{ $isCurrentWeek ? 'opacity-50' : '' }}">Текущая неделя</button>
            <button wire:click="nextWeek" class="rounded p-1.5 text-yt-muted hover:bg-yt-hover hover:text-yt-text" title="Следующая неделя">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </button>
        </div>
    </x-slot>

    <div class="border-b border-yt-border-soft bg-yt-surface/60 px-6 py-3">
        <span class="text-sm text-yt-muted">
            {{ $days->first()->format('d.m') }} — {{ $days->last()->format('d.m.Y') }},
            {{ $viewedUser->name }}:
        </span>
        <span class="text-sm font-semibold text-yt-accent-hover">{{ $formatMinutes($weekTotal) }} за неделю</span>
    </div>

    <div class="min-h-0 flex-1 overflow-auto p-4">
        <div class="grid min-w-max gap-3" style="grid-template-columns: repeat(7, 240px);">
            @foreach ($days as $day)
                @php
                    $dayKey = $day->toDateString();
                    $isToday = $day->isToday();
                @endphp
                <div wire:key="day-{{ $dayKey }}" class="flex flex-col rounded {{ $isToday ? 'bg-yt-surface ring-1 ring-yt-accent/40' : 'bg-yt-surface/60' }}">
                    <div class="flex items-baseline gap-2 px-3 py-2">
                        <span class="text-xs font-semibold uppercase tracking-wide {{ $isToday ? 'text-yt-accent-hover' : 'text-yt-muted' }}">
                            {{ ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'][$day->dayOfWeekIso - 1] }} {{ $day->format('d.m') }}
                        </span>
                        <span class="ml-auto text-xs {{ $dayTotals[$dayKey] > 0 ? 'font-medium text-yt-text' : 'text-yt-faint' }}">
                            {{ $dayTotals[$dayKey] > 0 ? $formatMinutes($dayTotals[$dayKey]) : '—' }}
                        </span>
                    </div>
                    <div class="flex-1 space-y-2 px-2 pb-2">
                        @foreach ($logs[$dayKey] ?? [] as $log)
                            <a href="{{ route('projects.board', [$log->task->project, 'tasks']) }}" wire:key="log-{{ $log->id }}"
                               class="block rounded border-l-[3px] bg-yt-card p-2.5 shadow-card transition-colors hover:bg-yt-hover"
                               style="border-left-color: {{ $log->task->priority->color() }}">
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="text-yt-faint">{{ $log->task->full_number }}</span>
                                    <span class="ml-auto font-semibold text-yt-accent-hover">{{ $log->formatted_duration }}</span>
                                </div>
                                <div class="mt-1 line-clamp-2 text-sm">{{ $log->task->title }}</div>
                                @if ($log->description)
                                    <div class="mt-1 line-clamp-2 text-xs text-yt-muted">{{ $log->description }}</div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
