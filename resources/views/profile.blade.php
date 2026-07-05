<x-app-layout>
    <x-slot name="header">
        <div class="flex h-12 items-center px-6">
            <h2 class="text-base font-semibold">Профиль</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-6">
            <div class="rounded-lg border border-yt-border-soft bg-yt-panel p-6">
                <div class="max-w-xl">
                    <livewire:profile.accent-color-form />
                </div>
            </div>

            <div class="rounded-lg border border-yt-border-soft bg-yt-panel p-6">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="rounded-lg border border-yt-border-soft bg-yt-panel p-6">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="rounded-lg border border-yt-border-soft bg-yt-panel p-6">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
