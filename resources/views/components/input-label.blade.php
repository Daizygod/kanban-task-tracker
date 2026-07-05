@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-1 block text-xs font-medium text-yt-muted']) }}>
    {{ $value ?? $slot }}
</label>
