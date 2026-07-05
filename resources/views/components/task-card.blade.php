@props(['task'])

@php
    $isDone = $task->status->is_final;
    $isOverdue = $task->due_date && $task->due_date->isPast() && ! $isDone;
    $hasOpenBlockers = $task->dependsOn->contains(fn ($dep) => ! $dep->status->is_final);
@endphp

{{-- Карточка доски: рамка #43454a, полоска приоритета 4px внутри рамки --}}
<div {{ $attributes }}
     data-task-id="{{ $task->id }}"
     wire:click="openTask({{ $task->id }})"
     class="cursor-pointer rounded border border-yt-border bg-yt-card py-2 pl-3 pr-2 shadow-card transition-[border-color] hover:border-yt-border-strong"
     style="box-shadow: inset 4px 0 0 {{ $task->priority->color }}, 0 0 3px 0 rgba(0,0,0,.1)">
    <div class="mb-2 text-sm leading-5">
        <span class="mr-1.5 whitespace-nowrap text-yt-muted {{ $isDone ? 'line-through' : '' }}">{{ $task->full_number }}</span><span class="{{ $isDone ? 'text-yt-muted' : 'text-yt-text' }}">{{ $task->title }}</span>
    </div>

    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-yt-muted">
        <span class="inline-flex items-center gap-1.5" title="Приоритет">
            <span class="h-[7px] w-[7px] rounded-full" style="background: {{ $task->priority->color }}"></span>
            {{ $task->priority->name }}
        </span>

        @if ($hasOpenBlockers)
            <span class="inline-flex items-center gap-1.5" title="Не может быть завершена: есть незакрытые зависимости">
                <span class="h-[7px] w-[7px] rounded-full bg-yt-blocked"></span>
                Заблокирована
            </span>
        @endif

        @if ($task->children_count > 0)
            <span class="inline-flex items-center gap-1 text-yt-faint" title="Подзадачи">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                {{ $task->children_count }}
            </span>
        @endif

        <span class="ml-auto inline-flex items-center gap-2">
            @if ($task->due_date)
                <span class="text-[11px] {{ $isOverdue ? 'font-medium text-yt-danger' : 'text-yt-faint' }}"
                      title="Срок: {{ $task->due_date->translatedFormat('d F Y') }}">{{ $task->due_date->format('d.m') }}</span>
            @endif
            @if ($task->assignee)
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-yt-accent text-[8px] font-semibold text-white"
                      title="Исполнитель: {{ $task->assignee->name }}">{{ $task->assignee->initials() }}</span>
            @endif
        </span>
    </div>
</div>
