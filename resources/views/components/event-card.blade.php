@props(['event'])

@php
    $cover = $event->cover_url ?? null;
    $title = $event->title;
    $mode = $event->mode; // online|offline|both
    $start = $event->start_date ? \Illuminate\Support\Carbon::parse($event->start_date) : null;
    $end = $event->end_date ? \Illuminate\Support\Carbon::parse($event->end_date) : null;
    try { if ($start) $start->locale('id'); if ($end) $end->locale('id'); } catch (\Throwable $e) {}

    $dateText = null;
    if ($start && $end) {
        $sameMonth = $start->format('mY') === $end->format('mY');
        $dateText = $sameMonth
            ? $start->translatedFormat('d M') . ' – ' . $end->translatedFormat('d')
            : $start->translatedFormat('d M') . ' – ' . $end->translatedFormat('d M');
    } elseif ($start) {
        $dateText = $start->translatedFormat('d M Y');
    }

    $priceLabel = 'Gratis';
    if (($event->price_type ?? 'fixed') === 'fixed' && (float)($event->price ?? 0) > 0) {
        $priceLabel = 'Rp ' . number_format((float)$event->price, 0, ',', '.');
    } elseif (($event->price_type ?? 'fixed') !== 'fixed') {
        $priceLabel = 'Donasi';
    }

    $modeText = match ($mode) {
        'online' => 'Online',
        'offline' => 'Offline',
        'both' => 'Online & Offline',
        default => 'Event',
    };
@endphp

@php
    $detailUrl = isset($event->slug) && $event->slug
        ? route('event.show', $event->slug)
        : route('event.index');
@endphp
<article class="relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs hover:shadow cursor-pointer">
    <a href="{{ $detailUrl }}" aria-label="Lihat detail {{ $title }}" class="absolute inset-0 z-10"></a>
    <div class="flex items-start gap-3 p-2">
        @if ($cover)
            <img src="{{ $cover }}" alt="{{ $title }}" class="h-24 w-24 rounded-lg object-cover" />
        @else
            <div class="h-16 w-24 rounded-lg bg-gray-100"></div>
        @endif

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <h3 class="line-clamp-2 text-[15px] font-semibold text-gray-900">{{ $title }}</h3>
                <button type="button" class="shrink-0 text-gray-400 hover:text-amber-500" aria-label="Simpan">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                        <path d="M6.32 2.577A49.255 49.255 0 0 1 12 2c1.937 0 3.86.19 5.68.577a2.11 2.11 0 0 1 1.64 2.06v14.217a1 1 0 0 1-1.55.835L12 16.25l-5.77 3.439a1 1 0 0 1-1.55-.835V4.636a2.11 2.11 0 0 1 1.64-2.06z" />
                    </svg>
                </button>
            </div>

            <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-gray-600">
                @if ($dateText)
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        <span>{{ $dateText }}</span>
                    </div>
                @endif
                <div class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    <span>{{ $modeText }}</span>
                </div>
            </div>

            <div class="mt-1 text-[13px] text-gray-500">
                <span>Mulai dari</span>
                <span class="ml-1 text-base font-bold text-gray-900">{{ $priceLabel }}</span>
            </div>
        </div>
    </div>
</article>
