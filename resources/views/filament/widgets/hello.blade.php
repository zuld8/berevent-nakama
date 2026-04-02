<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-2xl p-6"
         style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 40%, #0d9488 100%);">
        {{-- Decorative elements --}}
        <div class="absolute top-0 right-0 w-64 h-64 rounded-full opacity-10"
             style="background: radial-gradient(circle, #5eead4 0%, transparent 70%); transform: translate(30%, -30%);"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full opacity-8"
             style="background: radial-gradient(circle, #f59e0b 0%, transparent 70%); transform: translate(-30%, 30%);"></div>

        <div class="relative z-10 flex items-center gap-5">
            {{-- Avatar / Icon --}}
            <div class="flex-shrink-0">
                <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center ring-1 ring-white/20 shadow-lg">
                    <svg class="w-7 h-7 text-teal-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.841m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                    </svg>
                </div>
            </div>

            {{-- Text content --}}
            <div class="flex-1 min-w-0">
                <h2 class="text-xl font-bold text-white tracking-tight">
                    Selamat datang, {{ auth()->user()?->name ?? 'Admin' }}! 👋
                </h2>
                <p class="mt-1 text-sm text-teal-200/80">
                    <span class="font-semibold text-teal-300">Nakama Project Hub</span>
                    &mdash; Panel admin untuk mengelola event, donasi, dan kampanye.
                </p>
                <p class="mt-0.5 text-xs text-white/40">
                    {{ now()->translatedFormat('l, d F Y') }} &bull; {{ now()->format('H:i') }} WIB
                </p>
            </div>

            {{-- Quick action buttons --}}
            <div class="hidden sm:flex items-center gap-2">
                <a href="{{ url('/') }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-white/80 hover:text-white ring-1 ring-white/10 transition-all duration-200">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                    Website
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
