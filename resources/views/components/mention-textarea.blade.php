@props(['model', 'rows' => 2, 'placeholder' => ''])

{{-- Textarea с @-автокомплитом (юзеры и задачи проекта). Требует, чтобы
     Livewire-компонент страницы имел метод searchMentions (trait SearchesMentions) --}}
<div x-data="mentionBox" class="relative">
    <textarea x-ref="area"
              wire:model="{{ $model }}"
              rows="{{ $rows }}"
              placeholder="{{ $placeholder }}"
              x-on:input.debounce.250ms="onInput"
              x-on:keydown="onKeydown"
              x-on:blur="setTimeout(() => open = false, 200)"
              {{ $attributes->merge(['class' => 'yt-input']) }}></textarea>

    <div x-show="open" x-cloak
         class="absolute left-0 top-full z-40 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-yt-border bg-yt-panel py-1 shadow-modal">
        <template x-for="(item, index) in items" :key="index">
            {{-- mousedown.prevent: не отдаём фокус до вставки --}}
            <button type="button"
                    x-on:mousedown.prevent="pick(item)"
                    x-on:mouseenter="active = index"
                    class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm"
                    :class="index === active ? 'bg-yt-hover' : ''">
                <span class="shrink-0 text-yt-link" x-text="item.label"></span>
                <span class="truncate text-xs text-yt-muted" x-text="item.hint"></span>
            </button>
        </template>
    </div>
</div>
