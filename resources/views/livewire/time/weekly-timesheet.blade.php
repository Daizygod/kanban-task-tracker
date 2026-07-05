@php
    $formatMinutes = fn (int $minutes) => $minutes >= 60
        ? intdiv($minutes, 60).' ч'.($minutes % 60 ? ' '.($minutes % 60).' м' : '')
        : $minutes.' м';

    $monthsGenitive = [1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    $weekLabel = $days->first()->month === $days->last()->month
        ? $monthsGenitive[$days->first()->month].' '.$days->first()->day.' – '.$days->last()->day
        : $monthsGenitive[$days->first()->month].' '.$days->first()->day.' – '.$monthsGenitive[$days->last()->month].' '.$days->last()->day;
@endphp

<div class="flex h-full flex-col">
    <x-slot name="header">
        <div class="flex h-12 items-center px-6">
            <h1 class="text-base font-semibold">Учёт времени</h1>
        </div>
    </x-slot>

    <div class="min-h-0 flex-1 overflow-auto bg-yt-board">
        {{-- Тулбар недели: внутри компонента, чтобы wire:* работали --}}
        <div class="flex flex-wrap items-center gap-3 px-6 pb-1 pt-4">
            <span class="text-xl font-semibold capitalize">{{ $weekLabel }}</span>

            <select wire:change="selectUser($event.target.value)" class="yt-input w-52 py-1">
                @foreach ($teammates as $teammate)
                    <option value="{{ $teammate->id }}" @selected($teammate->id === $viewedUserId)>
                        {{ $teammate->name }}{{ $teammate->id === auth()->id() ? ' (я)' : '' }}
                    </option>
                @endforeach
            </select>

            <div class="ml-auto flex items-center gap-1">
                <button wire:click="previousWeek" class="rounded border border-yt-border p-1.5 text-yt-muted hover:bg-yt-hover hover:text-yt-text" title="Предыдущая неделя">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>
                <button wire:click="currentWeek" class="yt-btn-secondary py-1 {{ $isCurrentWeek ? 'opacity-50' : '' }}">Сегодня</button>
                <button wire:click="nextWeek" class="rounded border border-yt-border p-1.5 text-yt-muted hover:bg-yt-hover hover:text-yt-text" title="Следующая неделя">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
        </div>

        <div class="px-6 pb-3 text-sm text-yt-muted">
            Затраченное время <span class="font-semibold text-yt-text">{{ $formatMinutes($weekTotal) }}</span> из 40 ч
            <span class="mx-1 text-yt-faint">·</span> <span wire:key="viewed-{{ $viewedUser->id }}">{{ $viewedUser->name }}</span>
        </div>

        {{-- Неделя: 7 колонок на всю ширину, скролл только у доски --}}
        <div class="min-w-fit px-4 pb-6">
            <div class="grid gap-0" style="grid-template-columns: repeat(7, minmax(180px, 1fr));">
                @foreach ($days as $day)
                    @php
                        $dayKey = $day->toDateString();
                        $isToday = $day->isToday();
                        $isWeekend = $day->isWeekend();
                        $dayMinutes = $dayTotals[$dayKey];
                    @endphp
                    <div wire:key="day-{{ $dayKey }}"
                         class="flex min-h-[420px] flex-col {{ ! $loop->first ? 'border-l border-yt-board-border' : '' }} {{ $isWeekend ? 'bg-yt-selected/10' : '' }}">
                        {{-- Заголовок дня: «ПНД 5» + «X ч из 8» --}}
                        <div class="flex items-center gap-2 px-2.5 py-2 {{ $isToday ? 'border-b-2 border-yt-accent' : 'border-b border-yt-board-border' }}">
                            <span class="text-xs font-semibold uppercase tracking-wide {{ $isToday ? 'text-yt-accent-hover' : 'text-yt-text' }}">
                                {{ ['ПНД', 'ВТР', 'СРД', 'ЧТВ', 'ПТН', 'СУБ', 'ВСК'][$day->dayOfWeekIso - 1] }}
                            </span>
                            <span class="text-xs {{ $isToday ? 'text-yt-accent-hover' : 'text-yt-muted' }}">{{ $day->day }}</span>
                            <span class="ml-auto rounded px-1.5 py-0.5 text-[11px] {{ $dayMinutes > 0 ? 'bg-yt-warning/20 text-yt-warning' : 'bg-yt-border/30 text-yt-faint' }}">
                                {{ $dayMinutes > 0 ? $formatMinutes($dayMinutes) : '0 ч' }} из 8
                            </span>
                        </div>

                        <div class="flex-1 space-y-2 p-2">
                            @foreach ($logs[$dayKey] ?? [] as $log)
                                <a href="{{ route('projects.board', [$log->task->project, 'tasks']) }}" wire:key="log-{{ $log->id }}"
                                   class="block rounded border border-yt-border bg-yt-card p-2.5 shadow-card transition-colors hover:border-yt-border-strong"
                                   style="box-shadow: inset 0 -3px 0 {{ $log->task->priority->color }}, 0 0 3px 0 rgba(0,0,0,.1)">
                                    <div class="flex items-baseline gap-2 text-sm">
                                        <span class="text-yt-link">{{ $log->task->full_number }}</span>
                                        <span class="ml-auto font-medium text-yt-text">{{ $log->formatted_duration }}</span>
                                    </div>
                                    <div class="mt-1 line-clamp-2 text-sm text-yt-text">{{ $log->task->title }}</div>
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
</div>
