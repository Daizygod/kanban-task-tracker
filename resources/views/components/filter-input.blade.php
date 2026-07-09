@props([
    /** Имя Livewire-события, в которое уходит значение фильтра */
    'event' => 'board-filter',
    'placeholder' => 'Фильтр задач',
    /** Мета автокомплита: TaskFilter::meta($project) */
    'meta' => [],
    'value' => '',
    'width' => 'w-[320px]',
])

{{-- Подсказки клиентские (справочники в data-meta), поэтому компонент
     работает и в слоте шапки, где $wire недоступен --}}
<div class="relative" x-data="filterBox" data-event="{{ $event }}" data-meta="{{ json_encode($meta, JSON_UNESCAPED_UNICODE) }}">
    <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-yt-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
    <input type="text" x-ref="input" value="{{ $value }}"
           x-on:input="onInput"
           x-on:focus="suggest"
           x-on:click="suggest"
           x-on:keydown="onKeydown"
           x-on:blur="setTimeout(() => open = false, 200)"
           placeholder="{{ $placeholder }}"
           autocomplete="off" spellcheck="false"
           class="h-8 {{ $width }} rounded-lg border-yt-border bg-transparent pl-8 pr-3 text-sm text-yt-text placeholder-yt-muted focus:border-yt-accent focus:ring-1 focus:ring-yt-accent">

    <div x-show="open" x-cloak class="absolute left-0 top-full z-40 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-yt-border bg-yt-panel py-1 shadow-modal">
        <template x-for="(item, i) in items" :key="i">
            <button type="button" x-on:mousedown.prevent="pick(item)" x-on:mouseenter="active = i"
                    class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm"
                    :class="i === active ? 'bg-yt-selected/60 text-yt-text' : 'text-yt-text'">
                <span class="min-w-0 truncate" x-text="item.label"></span>
                <span class="ml-auto shrink-0 text-xs text-yt-faint" x-text="item.hint"></span>
            </button>
        </template>
    </div>
</div>
