<!DOCTYPE html>
<html lang="ru" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen flex-col items-center bg-yt-bg pt-6 sm:justify-center sm:pt-0">
            <a href="/" wire:navigate class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-yt-accent text-xl font-bold text-white">К</span>
                <span class="text-lg font-semibold">{{ config('app.name') }}</span>
            </a>

            <div class="mt-6 w-full overflow-hidden rounded-lg border border-yt-border-soft bg-yt-panel px-6 py-6 shadow-modal sm:max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
