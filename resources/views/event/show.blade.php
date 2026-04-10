@extends('layouts.storefront')

@section('title', $event->title)

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
                        <span class="block text-[12px] text-gray-500 ">Mulai dari</span>
                        <span class="block text-lg font-bold text-gray-900">{{ $priceLabel }}</span>
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
                        // Extract YouTube video ID dari URL
                        $replayYtId = null;
                        $replayRaw  = (string) $event->replay_url;
                        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/))([A-Za-z0-9_-]{11})/', $replayRaw, $ym)) {
                            $replayYtId = $ym[1];
                        }
                    @endphp
                    <div class="mt-6 rounded-2xl overflow-hidden bg-gray-900 shadow-lg">
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-800">
                            <span class="h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                            <span class="text-xs font-semibold text-gray-300 uppercase tracking-wide">🎬 Rekaman Event</span>
                        </div>
                        @if ($replayYtId)
                            <div class="relative w-full" style="aspect-ratio:16/9;">
                                <iframe
                                    src="https://www.youtube.com/embed/{{ $replayYtId }}?modestbranding=1&rel=0&showinfo=0&iv_load_policy=3&playsinline=1&color=white"
                                    class="absolute inset-0 w-full h-full"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    title="{{ $event->title }} - Rekaman">
                                </iframe>
                            </div>
                        @else
                            {{-- Fallback: URL langsung (bukan YouTube) --}}
                            <div class="p-4">
                                <a href="{{ $event->replay_url }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                        <path d="M8 5.25a.75.75 0 0 1 .75-.75h10.5a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-1.5 0V6h-9.75A.75.75 0 0 1 8 5.25z"/>
                                        <path d="M3.75 9a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5H5.56l8.47 8.47a.75.75 0 1 1-1.06 1.06L4.5 10.81v2.94a.75.75 0 0 1-1.5 0V9z"/>
                                    </svg>
                                    Tonton Rekaman
                                </a>
                            </div>
                        @endif
                        <div class="px-4 py-2 bg-gray-800 text-[11px] text-gray-500 text-center">
                            Akses eksklusif untuk peserta & pembeli rekaman
                        </div>
                    </div>
                @endif

                {{-- CTA moved to fixed bottom bar --}}
            </div>
        </div>
    </main>

    {{-- Price Selection Modal for Dynamic Pricing --}}
    @if (($event->price_type ?? 'fixed') !== 'fixed' && !$isExpired)
        <div id="priceModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-semibold text-gray-900">Pilih Nominal Donasi</h3>
                <p class="mt-1 text-sm text-gray-600">Silakan pilih atau masukkan nominal donasi Anda</p>
                
                <form method="post" action="{{ route('cart.add', $event->slug) }}" id="priceForm">
                    @csrf
                    <input type="hidden" name="custom_price" id="selectedPrice" value="">
                    
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <button type="button" onclick="selectPrice(50000)" class="price-option rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 hover:border-sky-500 hover:bg-sky-50 focus:border-sky-500 focus:bg-sky-50 focus:outline-none">
                            Rp 50.000
                        </button>
                        <button type="button" onclick="selectPrice(100000)" class="price-option rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 hover:border-sky-500 hover:bg-sky-50 focus:border-sky-500 focus:bg-sky-50 focus:outline-none">
                            Rp 100.000
                        </button>
                        <button type="button" onclick="selectPrice(200000)" class="price-option rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 hover:border-sky-500 hover:bg-sky-50 focus:border-sky-500 focus:bg-sky-50 focus:outline-none">
                            Rp 200.000
                        </button>
                        <button type="button" onclick="selectCustomPrice()" class="price-option rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 hover:border-sky-500 hover:bg-sky-50 focus:border-sky-500 focus:bg-sky-50 focus:outline-none">
                            Nominal Lain
                        </button>
                    </div>

                    <div id="customPriceInput" class="mt-4 hidden">
                        <label for="customAmount" class="block text-sm font-medium text-gray-700">Masukkan Nominal (Rp)</label>
                        <input type="number" id="customAmount" min="10000" step="1000" placeholder="Contoh: 150000" class="mt-1 block w-full rounded-xl border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button type="button" onclick="closeModal()" class="flex-1 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" class="flex-1 rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white shadow hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed" id="submitBtn" disabled>
                            Tambahkan
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
                    {{-- Already has access — scroll ke video --}}
                    <button onclick="document.querySelector('.bg-gray-900')?.scrollIntoView({behavior:'smooth'})"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white shadow hover:bg-sky-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                            <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                        </svg>
                        Tonton Rekaman
                    </button>

                @elseif ($event->hasReplay() && $replayPrice !== null)
                    {{-- Replay tersedia untuk dibeli --}}
                    @guest
                        <a href="{{ route('login') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white shadow hover:bg-sky-700">
                            🔐 Login untuk Beli Rekaman
                        </a>
                    @else
                        <a href="{{ route('event.replay.checkout', $event->slug) }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white shadow hover:bg-sky-700">
                            🎬 Beli Rekaman
                            @if ($replayPrice > 0)
                                — Rp {{ number_format($replayPrice, 0, ',', '.') }}
                            @else
                                (Gratis)
                            @endif
                        </a>
                    @endguest

                @else
                    <button disabled class="inline-flex w-full items-center justify-center rounded-xl bg-gray-400 px-4 py-3 text-sm font-medium text-white cursor-not-allowed">
                        ⛔ Event Sudah Berakhir
                    </button>
                @endif

            @else
                {{-- Event masih aktif --}}
                @guest
                    <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white shadow hover:bg-sky-700">Daftar Sekarang</a>
                @else
                    @if (($event->price_type ?? 'fixed') === 'fixed')
                        <form method="post" action="{{ route('cart.add', $event->slug) }}">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white shadow hover:bg-sky-700">Tambahkan Ke Keranjang</button>
                        </form>
                    @else
                        <button type="button" onclick="openModal()" class="inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white shadow hover:bg-sky-700">Tambahkan Ke Keranjang</button>
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
        function openModal() {
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
            if (customInput) {
                customInput.addEventListener('input', function() {
                    const value = parseInt(this.value) || 0;
                    if (value >= 10000) {
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
