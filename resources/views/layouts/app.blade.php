<!DOCTYPE html>
<html lang="ru" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @auth
            <meta name="centrifugo-token" content="{{ app(App\Services\Centrifugo::class)->connectionToken(auth()->id()) }}">
        @endauth

        <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-screen overflow-hidden font-sans">
        <div class="flex h-full">
            {{-- Сайдбар --}}
            <aside class="flex w-56 shrink-0 flex-col border-r border-yt-border-soft bg-yt-surface">
                <a href="{{ route('projects.index') }}" class="flex items-center gap-2 px-4 py-4">
                    <span class="flex h-7 w-7 items-center justify-center rounded bg-yt-accent text-sm font-bold text-white">К</span>
                    <span class="text-sm font-semibold tracking-wide">{{ config('app.name') }}</span>
                </a>

                <nav class="flex-1 space-y-0.5 overflow-y-auto px-2 pb-4">
                    <a href="{{ route('projects.index') }}"
                       class="flex items-center gap-2 rounded px-2 py-1.5 text-sm {{ request()->routeIs('projects.index') ? 'bg-yt-hover text-yt-text' : 'text-yt-muted hover:bg-yt-hover hover:text-yt-text' }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-3.75ZM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-8.25ZM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-2.25Z" /></svg>
                        Проекты
                    </a>
                    <a href="{{ route('time.index') }}"
                       class="flex items-center gap-2 rounded px-2 py-1.5 text-sm {{ request()->routeIs('time.index') ? 'bg-yt-hover text-yt-text' : 'text-yt-muted hover:bg-yt-hover hover:text-yt-text' }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        Моё время
                    </a>

                    @auth
                        @php($sidebarProjects = auth()->user()->projects()->orderBy('name')->get())
                        @if ($sidebarProjects->isNotEmpty())
                            <div class="px-2 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-yt-faint">Мои проекты</div>
                            @foreach ($sidebarProjects as $sidebarProject)
                                <a href="{{ route('projects.board', $sidebarProject) }}"
                                   class="flex items-center gap-2 rounded px-2 py-1.5 text-sm {{ request()->route('project')?->is($sidebarProject) ? 'bg-yt-hover text-yt-text' : 'text-yt-muted hover:bg-yt-hover hover:text-yt-text' }}">
                                    <span class="flex h-4 w-4 items-center justify-center rounded-sm bg-yt-panel text-[9px] font-bold text-yt-muted ring-1 ring-yt-border">{{ mb_substr($sidebarProject->key, 0, 1) }}</span>
                                    <span class="truncate">{{ $sidebarProject->name }}</span>
                                </a>
                            @endforeach
                        @endif
                    @endauth
                </nav>

                @auth
                    <div class="border-t border-yt-border-soft p-3" x-data="{ open: false }">
                        <button @click="open = !open" class="flex w-full items-center gap-2 rounded px-1 py-1 text-left hover:bg-yt-hover">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-yt-accent/80 text-xs font-semibold text-white">{{ auth()->user()->initials() }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm">{{ auth()->user()->name }}</span>
                                <span class="block truncate text-xs text-yt-faint">{{ auth()->user()->email }}</span>
                            </span>
                        </button>
                        <div x-show="open" class="mt-2 space-y-0.5" style="display: none;">
                            <a href="{{ route('profile') }}" class="block rounded px-2 py-1.5 text-sm text-yt-muted hover:bg-yt-hover hover:text-yt-text">Профиль</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full rounded px-2 py-1.5 text-left text-sm text-yt-muted hover:bg-yt-hover hover:text-yt-text">Выйти</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </aside>

            {{-- Контент --}}
            <div class="flex min-w-0 flex-1 flex-col">
                @if (isset($header))
                    <header class="flex h-14 shrink-0 items-center border-b border-yt-border-soft bg-yt-surface px-6">
                        {{ $header }}
                    </header>
                @endif

                <main class="min-h-0 flex-1 overflow-auto">
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Тосты --}}
        <div x-data="{
                toasts: [],
                add(detail) {
                    const id = Date.now() + Math.random();
                    this.toasts.push({ id, message: detail.message, type: detail.type ?? 'info' });
                    setTimeout(() => this.remove(id), 5000);
                },
                remove(id) {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                },
             }"
             @toast.window="add($event.detail)"
             class="pointer-events-none fixed bottom-4 right-4 z-[100] flex w-80 flex-col gap-2">
            <template x-for="toast in toasts" :key="toast.id">
                <div class="pointer-events-auto flex items-start gap-2 rounded-md border p-3 text-sm shadow-modal"
                     :class="toast.type === 'error'
                        ? 'border-yt-danger/60 bg-yt-danger/15 text-yt-text'
                        : 'border-yt-border bg-yt-panel text-yt-text'"
                     x-transition>
                    <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full" :class="toast.type === 'error' ? 'bg-yt-danger' : 'bg-yt-success'"></span>
                    <span class="flex-1" x-text="toast.message"></span>
                    <button @click="remove(toast.id)" class="text-yt-faint hover:text-yt-text">&times;</button>
                </div>
            </template>
        </div>
    </body>
</html>
