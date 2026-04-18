@extends('layouts.storefront')

@php
    // ── SEO prep (pakai data dari controller) ──────────────────────────────
    $seoTitle       = $event->title . ' — ' . config('app.name', 'Nakama Project Hub');
    $seoDesc        = $event->description
        ? \Illuminate\Support\Str::limit(strip_tags($event->description), 158)
        : 'Daftar & ikuti event ' . $event->title . ' di Nakama Project Hub. Tiket online, rekaman eksklusif, dan materi tersedia.';
    $seoUrl         = route('event.show', $event->slug);
    $seoImage       = $event->cover_url ?? null;
    $eventMode      = match((string) $event->mode) {
        'online'  => 'online',
        'offline' => 'offline',
        default   => 'mixed',
    };
    $isFree = ($event->price_type ?? 'fixed') !== 'fixed'
        || ((float)($event->price ?? 0)) === 0.0;
@endphp

@section('seo_title',       $seoTitle)
@section('seo_description', $seoDesc)
@section('seo_canonical',   $seoUrl)
@section('og_type',         'article')
@section('og_title',        $seoTitle)
@section('og_description',  $seoDesc)
@section('og_image',        $seoImage ?? '')

@push('json_ld')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context'    => 'https://schema.org',
    '@type'       => 'Event',
    'name'        => $event->title,
    'description' => $seoDesc,
    'url'         => $seoUrl,
    'image'       => $seoImage,
    'startDate'   => $event->start_date ? \Carbon\Carbon::parse($event->start_date)->toIso8601String() : null,
    'endDate'     => $event->end_date   ? \Carbon\Carbon::parse($event->end_date)->toIso8601String()   : null,
    'eventStatus' => 'https://schema.org/EventScheduled',
    'eventAttendanceMode' => match($eventMode) {
        'online'  => 'https://schema.org/OnlineEventAttendanceMode',
        'offline' => 'https://schema.org/OfflineEventAttendanceMode',
        default   => 'https://schema.org/MixedEventAttendanceMode',
    },
    'location' => $eventMode === 'online' ? [
        '@type' => 'VirtualLocation',
        'url'   => $seoUrl,
    ] : [
        '@type'   => 'Place',
        'name'    => 'Venue',
        'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'ID'],
    ],
    'organizer' => $event->organization ? [
        '@type' => 'Organization',
        'name'  => $event->organization->name,
        'url'   => config('app.url'),
    ] : null,
    'offers' => [[
        '@type'         => 'Offer',
        'url'           => $seoUrl,
        'price'         => $isFree ? '0' : (string) (int) ($event->price ?? 0),
        'priceCurrency' => 'IDR',
        'availability'  => 'https://schema.org/InStock',
    ]],
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')

    @php
        $start = $event->start_date ? \Illuminate\Support\Carbon::parse($event->start_date) : null;
        $end = $event->end_date ? \Illuminate\Support\Carbon::parse($event->end_date) : null;
        try {
            if ($start)
                $start->locale('id');
            if ($end)
                $end->locale('id');
        } catch (\Throwable $e) {
        }
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
        $modeText = match ($event->mode) {
            'online' => 'Online', 'offline' => 'Offline', 'both' => 'Online & Offline', default => 'Event'
        };
        $priceLabel = 'Gratis';
        if (($event->price_type ?? 'fixed') === 'fixed' && (float) ($event->price ?? 0) > 0) {
            $priceLabel = 'Rp ' . number_format((float) $event->price, 0, ',', '.');
        } elseif (($event->price_type ?? 'fixed') !== 'fixed') {
            $priceLabel = 'Donasi';
        }
        // Check if event has expired
        $isExpired = $end && $end->endOfDay()->isPast();
    @endphp

    <main class="mx-auto max-w-2xl pb-24">
        <!-- Hero Cover -->
        <div class="relative h-full w-full overflow-hidden">
            @if ($event->cover_url)
                <img src="{{ $event->cover_url }}" alt="{{ $event->title }}" class="h-full w-full object-cover" />
            @else
                <div class="h-full w-full bg-gray-200"></div>
            @endif
            <!-- Back button -->
            <a href="{{ url()->previous() ?: route('event.index') }}" aria-label="Kembali"
               class="absolute left-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/90 ring-1 ring-black/10 shadow hover:bg-white">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"></path>
                </svg>
            </a>
        </div>

        <!-- Card Body -->
        <div class="mx-4 rounded-2xl bg-white overflow-hidden">
            <div class="py-4">
                <div class="flex items-start justify-between gap-3">
                    <h1 class="text-lg font-semibold text-gray-900 leading-6 w-2/3">{{ $event->title }}</h1>
                    <div class="text-right w-1/3">
                        @if (($event->price_type ?? 'fixed') !== 'fixed')
                            {{-- Infak/Donasi --}}
                            @if ((float)($event->price ?? 0) > 0)
                                <span class="block text-xs text-gray-400 line-through">Rp {{ number_format((float)$event->price, 0, ',', '.') }}</span>
                            @endif
                            <span class="block text-base font-bold text-teal-700">Infaq Terbaik</span>
                        @else
                            <span class="block text-[12px] text-gray-500">Mulai dari</span>
                            <span class="block text-lg font-bold text-gray-900">{{ $priceLabel }}</span>
                        @endif
                    </div>
                </div>
                @if ($event->organization)
                    <x-org-badge :org="$event->organization" size="sm" />
                @endif

                <div class="mt-3 space-y-1 text-[13px] text-gray-600">
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="h-4 w-4 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                        <span>{{ $modeText }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="h-4 w-4 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        <span>{{ $dateText ?? 'Tanggal menyusul' }}</span>
                    </div>
                </div>

                @if ($event->description)
                    <h2 class="mt-5 text-sm font-semibold text-gray-900">Tentang Event</h2>
                    <p class="mt-2 text-[13px] leading-6 text-gray-700">
                        {{ strip_tags($event->description) }}</p>
                @endif

                @if (($event->materials ?? collect())->count() > 0)
                    <h2 class="mt-6 text-sm font-semibold text-gray-900">Materi</h2>
                    <div class="relative mt-2">
                        <div class="absolute left-4 top-0 bottom-0 w-px bg-gray-200"></div>
                        <div class="space-y-5">
                            @foreach ($event->materials as $mat)
                                @php $mentor = $mat->mentor; @endphp
                                <div class="relative pl-10 mb-2">
                                    <span class="absolute left-3 top-1.5 h-3 w-3 rounded-full bg-sky-500 ring-4 ring-white"></span>
                                    <h3 class="text-[13px] font-semibold text-sky-700 uppercase">{{ $mat->title }}</h3>
                                    <div class="mt-2 flex items-center gap-3 text-[13px] text-gray-700">
                                        @if ($mentor)
                                            @if ($mentor->photo_url)
                                                <img src="{{ $mentor->photo_url }}" alt="{{ $mentor->name }}" class="h-9 w-9 rounded-md object-cover" />
                                            @else
                                                <div class="h-9 w-9 rounded-md bg-gray-100 flex items-center justify-center text-gray-400">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 8a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20a8 8 0 1116 0" /></svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-medium text-[13px] text-gray-900">{{ $mentor->name }}</div>
                                                @if ($mentor->profession)
                                                    <div class="text-[12px] text-gray-600">{{ $mentor->profession }}</div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <div class="mt-2 flex items-center gap-1.5 text-[13px] text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                        <span>{{ optional($mat->date_at)->format('d M Y') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Replay Video Section (hanya tampil kalau event selesai + punya akses) --}}
                @if ($isExpired && $event->hasReplay() && $canWatch)
                    @php
                        // Thumbnail aman: hanya pakai video ID untuk gambar preview
                        // Video ID TIDAK diekspos ke JS langsung — load via API
                        $replayRaw = (string) $event->replay_url;
                        $replayYtId = null;
                        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/))([A-Za-z0-9_-]{11})/', $replayRaw, $ym)) {
                            $replayYtId = $ym[1];
                        }
                        // Thumbnail untuk preview (ID di sini aman — hanya gambar, bukan embed)
                        $thumbUrl = $replayYtId
                            ? "https://i.ytimg.com/vi/{$replayYtId}/hqdefault.jpg"
                            : null;
                        // API URL — embed URL di-generate server-side, bukan client
                        $replayApiUrl = route('api.event.replay-url', $event->slug);
                    @endphp

                    <div class="mt-6 rounded-2xl overflow-hidden bg-gray-900 shadow-lg">
                        {{-- Header --}}
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-800">
                            <span class="h-2 w-2 rounded-full bg-sky-400 animate-pulse"></span>
                            <span class="text-xs font-semibold text-white uppercase tracking-wider">🎬 Rekaman Event</span>
                            <span class="ml-auto text-[10px] text-gray-500">Nakama Project Hub</span>
                        </div>

                        {{-- FACADE — klik → fetch API → render iframe --}}
                        <div id="replay-facade"
                             class="relative w-full cursor-pointer group"
                             style="aspect-ratio:16/9;background:#111;"
                             onclick="loadReplaySecure()">

                            {{-- Thumbnail (preview only, bukan embed) --}}
                            @if($thumbUrl)
                                <img src="{{ $thumbUrl }}"
                                     alt="Rekaman {{ $event->title }}"
                                     class="absolute inset-0 w-full h-full object-cover opacity-80 group-hover:opacity-60 transition-opacity duration-200"/>
                            @else
                                <div class="absolute inset-0 bg-gray-800"></div>
                            @endif

                            <div class="absolute inset-0 bg-black/30"></div>

                            {{-- Judul event --}}
                            <div class="absolute top-3 left-3 right-3">
                                <div class="text-white text-sm font-semibold drop-shadow line-clamp-2">
                                    {{ $event->title }}
                                </div>
                            </div>

                            {{-- Tombol play custom Nakama --}}
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div id="replay-play-btn"
                                         class="flex items-center justify-center w-16 h-16 rounded-full bg-sky-500 shadow-2xl group-hover:bg-sky-400 group-hover:scale-110 transition-all duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="w-7 h-7 ml-1">
                                            <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <span id="replay-play-label"
                                          class="text-white text-xs font-medium bg-black/40 px-3 py-1 rounded-full backdrop-blur-sm">
                                        Tap untuk Putar
                                    </span>
                                </div>
                            </div>

                            {{-- Badge eksklusif --}}
                            <div class="absolute bottom-3 right-3">
                                <span class="text-[10px] font-semibold text-white bg-sky-600/80 px-2 py-0.5 rounded-full backdrop-blur-sm">
                                    🔒 Eksklusif
                                </span>
                            </div>
                        </div>

                        {{-- Error message --}}
                        <div id="replay-error" class="hidden px-4 py-3 bg-red-900/50 text-red-300 text-xs text-center"></div>

                        {{-- Placeholder iframe --}}
                        <div id="replay-player" class="hidden relative w-full" style="aspect-ratio:16/9;"></div>

                        <div class="px-4 py-2 bg-gray-800 text-[11px] text-gray-400 text-center">
                            Akses eksklusif · Nakama Project Hub
                        </div>
                    </div>

                    <script>
                    (function () {
                        var apiUrl  = '{{ $replayApiUrl }}';
                        var csrfToken = '{{ csrf_token() }}';
                        var loading = false;

                        window.loadReplaySecure = function () {
                            if (loading) return;
                            loading = true;

                            // Tampilkan loading state
                            var btn   = document.getElementById('replay-play-btn');
                            var label = document.getElementById('replay-play-label');
                            var errEl = document.getElementById('replay-error');
                            if (btn)   btn.classList.add('opacity-50', 'scale-95');
                            if (label) label.textContent = 'Memuat...';

                            // Fetch embed URL dari server (auth + rate limit terjadi di sini)
                            fetch(apiUrl, {
                                method:  'GET',
                                headers: {
                                    'Accept':           'application/json',
                                    'X-CSRF-TOKEN':     csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            })
                            .then(function (res) {
                                if (!res.ok) {
                                    return res.json().then(function (d) {
                                        throw new Error(d.error || 'Gagal memuat rekaman.');
                                    });
                                }
                                return res.json();
                            })
                            .then(function (data) {
                                var facade = document.getElementById('replay-facade');
                                var player = document.getElementById('replay-player');
                                if (!facade || !player) return;

                                if (data.type === 'direct') {
                                    // Buka di tab baru untuk non-YouTube
                                    window.open(data.embed_url, '_blank', 'noopener');
                                    loading = false;
                                    return;
                                }

                                // Render iframe dengan embed URL dari server
                                var iframe = document.createElement('iframe');
                                iframe.src = data.embed_url; // embed URL sudah final dari server
                                iframe.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;';
                                iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen';
                                iframe.allowFullscreen = true;
                                iframe.title = 'Rekaman Event';

                                player.style.position = 'relative';
                                player.appendChild(iframe);

                                facade.classList.add('hidden');
                                player.classList.remove('hidden');
                            })
                            .catch(function (err) {
                                loading = false;
                                var btn   = document.getElementById('replay-play-btn');
                                var label = document.getElementById('replay-play-label');
                                var errEl = document.getElementById('replay-error');
                                if (btn)   btn.classList.remove('opacity-50', 'scale-95');
                                if (label) label.textContent = 'Tap untuk Putar';
                                if (errEl) {
                                    errEl.textContent = '⚠️ ' + (err.message || 'Gagal memuat. Coba lagi.');
                                    errEl.classList.remove('hidden');
                                }
                            });
                        };
                    })();
                    </script>
                @endif

                {{-- Materi & File (hanya untuk yang punya akses replay/tiket) --}}
                @if ($isExpired && $canWatch && $event->resources->isNotEmpty())
                    <div class="mt-4 rounded-2xl overflow-hidden bg-white border border-gray-200 shadow-sm">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-100 bg-gray-50">
                            <span class="text-sm font-semibold text-gray-800">📎 Materi Event</span>
                            <span class="ml-auto text-[11px] text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                                {{ $event->resources->count() }} file
                            </span>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @foreach ($event->resources as $res)
                                <li class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition">
                                    {{-- Icon --}}
                                    <span class="text-xl shrink-0">{{ $res->icon() }}</span>

                                    {{-- Info --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 truncate">
                                            {{ $res->label }}
                                        </div>
                                        @if($res->humanSize())
                                            <div class="text-[11px] text-gray-400 mt-0.5">{{ $res->humanSize() }}</div>
                                        @endif
                                    </div>

                                    {{-- Download button --}}
                                    <a href="{{ route('event.resource.download', [$event->slug, $res->id]) }}"
                                       class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-sky-50 border border-sky-200 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-100 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                                            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                                            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                                        </svg>
                                        Download
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="px-4 py-2 bg-gray-50 text-[10px] text-gray-400 text-center border-t border-gray-100">
                            🔒 Materi eksklusif untuk peserta & pembeli rekaman
                        </div>
                    </div>
                @endif

                {{-- CTA moved to fixed bottom bar --}}

            </div>
        </div>
    </main>

    {{-- Price Selection Modal for Dynamic Pricing --}}
    @if (($event->price_type ?? 'fixed') !== 'fixed' && !$isExpired)
        @php $minInfak = (int) ($event->min_price ?? 10000); @endphp
        <div id="priceModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-semibold text-gray-900">Pilih Nominal Infak</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Minimum infak: <strong class="text-teal-700">Rp {{ number_format($minInfak, 0, ',', '.') }}</strong>
                </p>

                <form method="post" action="{{ route('cart.add', $event->slug) }}" id="priceForm">
                    @csrf
                    <input type="hidden" name="custom_price" id="selectedPrice" value="">

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @foreach ([50000, 100000, 200000, 500000] as $opt)
                            @if ($opt >= $minInfak)
                                <button type="button" onclick="selectPrice({{ $opt }})"
                                        class="price-option rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 hover:border-teal-500 hover:bg-teal-50 focus:border-teal-500 focus:bg-teal-50 focus:outline-none">
                                    Rp {{ number_format($opt, 0, ',', '.') }}
                                </button>
                            @endif
                        @endforeach
                        <button type="button" onclick="selectCustomPrice()"
                                class="price-option rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 hover:border-teal-500 hover:bg-teal-50 focus:border-teal-500 focus:bg-teal-50 focus:outline-none">
                            Nominal Lain
                        </button>
                    </div>

                    <div id="customPriceInput" class="mt-4 hidden">
                        <label for="customAmount" class="block text-sm font-medium text-gray-700">
                            Masukkan Nominal (Rp) — minimal Rp {{ number_format($minInfak, 0, ',', '.') }}
                        </label>
                        <input type="number" id="customAmount"
                               min="{{ $minInfak }}" step="1000"
                               placeholder="Contoh: {{ number_format($minInfak, 0, '', '') }}"
                               class="mt-1 block w-full rounded-xl border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button type="button" onclick="closeModal()"
                                class="flex-1 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit"
                                class="flex-1 rounded-xl bg-teal-600 px-4 py-3 text-sm font-medium text-white shadow hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                id="submitBtn" disabled>
                            Tambahkan ke Keranjang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Fixed bottom CTA --}}
    <div class="fixed inset-x-0 bottom-0 z-50">
        <div class="mx-auto max-w-2xl bg-white/95 backdrop-blur border-t border-gray-200 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] px-4 py-3"
             style="padding-bottom: calc(env(safe-area-inset-bottom,0px) + 12px);">

            @if (session('success'))
                <div class="mb-2 rounded-xl bg-green-50 border border-green-200 px-3 py-2 text-xs text-green-700 text-center font-medium">
                    ✅ {{ session('success') }}
                </div>
            @endif

            @if ($isExpired)
                {{-- Event sudah berakhir --}}
                @if ($event->hasReplay() && $canWatch)
                    {{-- Already has access --}}
                    <button onclick="document.querySelector('.bg-gray-900')?.scrollIntoView({behavior:'smooth'})"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-3 text-sm font-medium text-white shadow hover:bg-teal-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                            <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                        </svg>
                        Tonton Rekaman
                    </button>

                @elseif ($event->hasReplay() && $replayPrice !== null)
                    {{-- Replay untuk dibeli: Beli Rekaman | 🛒 --}}
                    @guest
                        <a href="{{ route('login') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-3 text-sm font-medium text-white shadow hover:bg-teal-700">
                            🔐 Login untuk Beli Rekaman
                        </a>
                    @else
                        <div class="flex items-center gap-2">
                            {{-- Primary: Beli Rekaman Sekarang --}}
                            <form method="post" action="{{ route('cart.replay.buy-now', $event->slug) }}" class="flex-1">
                                @csrf
                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow hover:bg-teal-700 active:scale-95 transition-transform">
                                    🎬 Beli Rekaman
                                    @if ($replayPrice > 0)
                                        <span class="opacity-80 text-xs font-normal">Rp {{ number_format($replayPrice, 0, ',', '.') }}</span>
                                    @endif
                                </button>
                            </form>
                            {{-- Secondary: Tambah ke Keranjang saja --}}
                            <form method="post" action="{{ route('cart.replay.add', $event->slug) }}">
                                @csrf
                                <button type="submit" title="Tambah ke Keranjang"
                                        class="inline-flex flex-col items-center justify-center gap-0.5 rounded-xl border-2 border-teal-600 px-3 py-2.5 text-teal-700 hover:bg-teal-50 active:scale-95 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                                    </svg>
                                    <span class="text-[10px] font-semibold leading-none">Keranjang</span>
                                </button>
                            </form>
                        </div>
                    @endguest

                @else
                    <button disabled class="inline-flex w-full items-center justify-center rounded-xl bg-gray-300 px-4 py-3 text-sm font-medium text-gray-500 cursor-not-allowed">
                        ⛔ Event Sudah Berakhir
                    </button>
                @endif

            @else
                {{-- Event masih aktif --}}
                @guest
                    <a href="{{ route('login') }}"
                       class="inline-flex w-full items-center justify-center rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow hover:bg-teal-700">
                        Daftar Sekarang
                    </a>
                @else
                    @if (($event->price_type ?? 'fixed') === 'fixed')
                        {{-- Fixed price: Beli Sekarang | 🛒 --}}
                        <div class="flex items-center gap-2">
                            {{-- Primary: Beli Sekarang (langsung checkout) --}}
                            <form method="post" action="{{ route('cart.buy-now', $event->slug) }}" class="flex-1">
                                @csrf
                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow hover:bg-teal-700 active:scale-95 transition-transform">
                                    Beli Sekarang
                                </button>
                            </form>
                            {{-- Secondary: Tambah ke Keranjang saja --}}
                            <form method="post" action="{{ route('cart.add', $event->slug) }}">
                                @csrf
                                <button type="submit" title="Tambah ke Keranjang"
                                        class="inline-flex flex-col items-center justify-center gap-0.5 rounded-xl border-2 border-teal-600 px-3 py-2.5 text-teal-700 hover:bg-teal-50 active:scale-95 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                                    </svg>
                                    <span class="text-[10px] font-semibold leading-none">Keranjang</span>
                                </button>
                            </form>
                        </div>
                    @else
                        {{-- Donation/Infak: modal → Beli Sekarang | modal → 🛒 --}}
                        <div class="flex items-center gap-2">
                            {{-- Primary: buka modal lalu buy now --}}
                            <button type="button" onclick="openModal('buynow')"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow hover:bg-teal-700 active:scale-95 transition-transform">
                                Beli Sekarang
                            </button>
                            {{-- Secondary: buka modal lalu add to cart saja --}}
                            <button type="button" onclick="openModal('addcart')"
                                    class="inline-flex flex-col items-center justify-center gap-0.5 rounded-xl border-2 border-teal-600 px-3 py-2.5 text-teal-700 hover:bg-teal-50 active:scale-95 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                                </svg>
                                <span class="text-[10px] font-semibold leading-none">Keranjang</span>
                            </button>
                        </div>
                    @endif
                @endguest
            @endif
        </div>
    </div>


    <script>
        // Hide global bottom nav on detail page
        (function(){
            const hideNav = () => {
                const nav = document.querySelector('nav.bottom-nav');
                if (nav) nav.style.display = 'none';
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', hideNav);
            } else { hideNav(); }
        })();

        // Price selection modal functions
        var _modalMode = 'buynow'; // 'buynow' | 'addcart'
        var _buyNowUrl = '{{ route('cart.buy-now', $event->slug) }}';
        var _addCartUrl = '{{ route('cart.add', $event->slug) }}';

        function openModal(mode) {
            _modalMode = mode || 'buynow';
            var form = document.getElementById('priceForm');
            var btn  = document.getElementById('submitBtn');
            if (form) form.action = (_modalMode === 'buynow') ? _buyNowUrl : _addCartUrl;
            if (btn)  btn.textContent = (_modalMode === 'buynow') ? 'Beli Sekarang' : 'Tambahkan ke Keranjang';
            document.getElementById('priceModal').classList.remove('hidden');
            document.getElementById('priceModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('priceModal').classList.add('hidden');
            document.getElementById('priceModal').classList.remove('flex');
            resetPriceSelection();
        }

        function resetPriceSelection() {
            document.getElementById('selectedPrice').value = '';
            document.getElementById('customPriceInput').classList.add('hidden');
            document.getElementById('customAmount').value = '';
            document.getElementById('submitBtn').disabled = true;
            document.querySelectorAll('.price-option').forEach(btn => {
                btn.classList.remove('border-sky-500', 'bg-sky-50');
                btn.classList.add('border-gray-200', 'bg-white');
            });
        }

        function selectPrice(amount) {
            document.getElementById('selectedPrice').value = amount;
            document.getElementById('customPriceInput').classList.add('hidden');
            document.getElementById('customAmount').value = '';
            document.getElementById('submitBtn').disabled = false;
            
            // Update button styles
            document.querySelectorAll('.price-option').forEach(btn => {
                btn.classList.remove('border-sky-500', 'bg-sky-50');
                btn.classList.add('border-gray-200', 'bg-white');
            });
            event.target.classList.remove('border-gray-200', 'bg-white');
            event.target.classList.add('border-sky-500', 'bg-sky-50');
        }

        function selectCustomPrice() {
            document.getElementById('customPriceInput').classList.remove('hidden');
            document.getElementById('selectedPrice').value = '';
            document.getElementById('submitBtn').disabled = true;
            
            // Update button styles
            document.querySelectorAll('.price-option').forEach(btn => {
                btn.classList.remove('border-sky-500', 'bg-sky-50');
                btn.classList.add('border-gray-200', 'bg-white');
            });
            event.target.classList.remove('border-gray-200', 'bg-white');
            event.target.classList.add('border-sky-500', 'bg-sky-50');
            
            document.getElementById('customAmount').focus();
        }

        // Handle custom amount input
        document.addEventListener('DOMContentLoaded', function() {
            const customInput = document.getElementById('customAmount');
            const minInfak = {{ $minInfak ?? 10000 }};
            if (customInput) {
                customInput.addEventListener('input', function() {
                    const value = parseInt(this.value) || 0;
                    if (value >= minInfak) {
                        document.getElementById('selectedPrice').value = value;
                        document.getElementById('submitBtn').disabled = false;
                    } else {
                        document.getElementById('selectedPrice').value = '';
                        document.getElementById('submitBtn').disabled = true;
                    }
                });
            }

            // Close modal on backdrop click
            const modal = document.getElementById('priceModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });
            }
        });
    </script>
@endsection
