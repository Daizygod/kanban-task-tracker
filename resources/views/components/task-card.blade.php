@props(['task'])

@php
    $isDone = $task->status->is_final;
    $isOverdue = $task->due_date && $task->due_date->isPast() && ! $isDone;
    $hasOpenBlockers = $task->dependsOn->contains(fn ($dep) => ! $dep->status->is_final);
@endphp

<div {{ $attributes }}
     data-task-id="{{ $task->id }}"
     wire:click="openTask({{ $task->id }})"
     class="cursor-pointer rounded border-l-[3px] bg-yt-card p-2.5 shadow-card transition-colors hover:bg-yt-hover"
     style="border-left-color: {{ $task->priority->color() }}">
    <div class="flex items-center gap-1.5 text-xs text-yt-faint">
        <span class="{{ $isDone ? 'line-through' : '' }}">{{ $task->full_number }}</span>
        <span class="text-yt-faint/70" title="Приоритет: {{ $task->priority->label() }}">{{ $task->priority->label() }}</span>
        @if ($hasOpenBlockers)
            <svg class="h-3.5 w-3.5 text-yt-danger" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <title>Заблокирована зависимостями</title>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
        @endif
    </div>

    <div class="mt-1 line-clamp-2 text-sm leading-snug {{ $isDone ? 'text-yt-muted' : 'text-yt-text' }}">{{ $task->title }}</div>

    <div class="mt-2 flex items-center gap-2">
        @if ($task->assignee)
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-yt-accent/70 text-[9px] font-semibold text-white"
                  title="Исполнитель: {{ $task->assignee->name }}">{{ $task->assignee->initials() }}</span>
        @else
            <span class="flex h-5 w-5 items-center justify-center rounded-full border border-dashed border-yt-border text-[9px] text-yt-faint" title="Исполнитель не назначен">—</span>
        @endif

        @if ($task->children_count > 0)
            <span class="yt-chip" title="Подзадачи">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" /></svg>
                {{ $task->children_count }}
            </span>
        @endif

        @if ($task->due_date)
            <span class="ml-auto text-[11px] {{ $isOverdue ? 'font-medium text-yt-danger' : 'text-yt-faint' }}"
                  title="Срок: {{ $task->due_date->translatedFormat('d F Y') }}">
                {{ $task->due_date->format('d.m') }}
            </span>
        @endif
    </div>
</div>
