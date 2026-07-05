<section>
    <header>
        <h2 class="text-lg font-medium text-yt-text">Акцентный цвет</h2>
        <p class="mt-1 text-sm text-yt-muted">
            Цвет кнопок, ссылок навигации, аватарок и индикатора загрузки. По умолчанию — фуксия.
        </p>
    </header>

    <form wire:submit="save" class="mt-5 space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($presets as $hex => $label)
                <button type="button" wire:click="$set('accentColor', '{{ $hex }}')"
                        class="flex h-8 w-8 items-center justify-center rounded-full ring-offset-2 ring-offset-yt-panel transition-shadow {{ mb_strtoupper($accentColor) === $hex ? 'ring-2 ring-yt-text' : 'hover:ring-2 hover:ring-yt-muted' }}"
                        style="background: {{ $hex }}" title="{{ $label }}"></button>
            @endforeach

            <label class="ml-2 flex items-center gap-2 text-sm text-yt-muted">
                Свой:
                <input type="color" wire:model.live="accentColor"
                       class="h-8 w-10 cursor-pointer rounded border border-yt-border bg-transparent p-0.5">
            </label>
        </div>

        @error('accentColor') <p class="text-xs text-yt-danger">{{ $message }}</p> @enderror

        <div class="flex items-center gap-3">
            <button type="submit" class="yt-btn-primary" style="background: {{ $accentColor }}">Сохранить</button>
            <span class="text-xs text-yt-faint">Текущий: {{ $accentColor }}</span>
        </div>
    </form>
</section>
