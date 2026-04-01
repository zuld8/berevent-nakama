<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-sky-400 to-sky-600 flex items-center justify-center shadow-lg shadow-sky-200 dark:shadow-sky-900/30">
                <x-heroicon-o-rocket-launch class="w-6 h-6 text-white" />
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    Selamat datang, {{ auth()->user()?->name ?? 'Admin' }}! 👋
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-sky-600 dark:text-sky-400">Nakama Project Hub</span>
                    &mdash; Panel admin untuk mengelola event, donasi, dan kampanye.
                    Hari ini {{ now()->translatedFormat('l, d F Y') }}.
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
