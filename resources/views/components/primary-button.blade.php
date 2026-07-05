<button {{ $attributes->merge(['type' => 'submit', 'class' => 'yt-btn-primary']) }}>
    {{ $slot }}
</button>
