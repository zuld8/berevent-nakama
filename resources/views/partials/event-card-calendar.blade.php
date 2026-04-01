@php
    // Expected: $event, optional: $ticketCount
    $cover = $event->cover_url ?? null;
    $title = $event->title ?? '';
    $mode = $event->mode ?? null; // online|offline|both
    $start = $event->start_date ? \Illuminate\Support\Carbon::parse($event->start_date) : null;
    $end = $event->end_date ? \Illuminate\Support\Carbon::parse($event->end_date) : null;
    try { if ($start) $start->locale('id'); if ($end) $end->locale('id'); } catch (\Throwable $e) {}

    $dateText = null;
    if ($start && $end) {
        $sameYear = $start->format('Y') === $end->format('Y');
        $sameMonth = $start->format('mY') === $end->format('mY');
        if ($sameMonth && $sameYear) {
            $dateText = $start->translatedFormat('d') . ' – ' . $end->translatedFormat('d M Y');
        } elseif ($sameYear) {
            $dateText = $start->translatedFormat('d M') . ' – ' . $end->translatedFormat('d M Y');
        } else {
            $dateText = $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y');
        }
    } elseif ($start) {
        $dateText = $start->translatedFormat('d M Y');
    }

    $modeText = match ($mode) {
        'online' => 'Online',
        'offline' => 'Offline',
        'both' => 'Online & Offline',
        default => 'Event',
    };
    $count = (int) ($ticketCount ?? 0);
    $codes = array_values(array_filter((array)($tickets ?? [])));
    // Status event
    $now = \Illuminate\Support\Carbon::now();
    $status = null;
    if ($end && $end->lt($now)) { $status = 'Selesai'; }
    elseif ($start && $start->gt($now)) { $status = 'Akan Datang'; }
    else { $status = 'Berjalan'; }
@endphp

<article class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs hover:shadow">
    <a href="{{ route('calendar.show', $event->slug) }}" class="absolute inset-0 z-10" aria-label="Lihat tiket {{ $title }}"></a>

    <div class="relative overflow-hidden">
        @if ($cover)
            <img src="{{ $cover }}" alt="{{ $title }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]" />
        @else
            <div class="h-36 w-full bg-gray-100"></div>
        @endif

        <div class="absolute left-2 top-2 flex items-center gap-1">
            <span class="inline-flex items-center rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-gray-800 ring-1 ring-black/10">{{ $status }}</span>
        </div>
        <div class="absolute right-2 top-2 flex items-center gap-1">
            @if($count > 0)
                <span class="inline-flex items-center rounded-full bg-indigo-600 px-2 py-0.5 text-[11px] font-semibold text-white shadow">Tiket {{ $count }}</span>
            @endif
            <a href="{{ route('calendar.print', $event->slug) }}" target="_blank"
               class="relative z-20 pointer-events-auto inline-flex items-center rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-sky-700 ring-1 ring-sky-200 hover:bg-white"
               aria-label="Download PDF tiket">
                PDF
            </a>
        </div>
    </div>

    <div class="p-3">
        <h3 class="line-clamp-2 text-[15px] font-semibold text-gray-900">{{ $title }}</h3>
        <div class="mt-2 space-y-1 text-[13px] text-gray-600">
            <div class="flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                <span>{{ $modeText }}</span>
            </div>
            @if ($dateText)
                <div class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span>{{ $dateText }}</span>
                </div>
            @endif
        </div>
    </div>
</article>
