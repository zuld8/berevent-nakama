<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $metaTitle = $c->meta_title ?: $c->title;
        $metaDesc = $c->meta_description ?: ($c->summary ?: '');
        $mSorted = $c->media->sortBy('sort_order');
        $mDesktop = optional($mSorted->firstWhere('platform', 'desktop'))->url;
        $mMobile = optional($mSorted->firstWhere('platform', 'mobile'))->url;
        $fallbackCover = optional($mSorted->first())->url;
        $metaImage = $c->meta_image_url ?: ($mDesktop ?: ($mMobile ?: $fallbackCover));
        $metaUrl = route('campaign.show', $c->slug);
    @endphp
    <title>{{ $metaTitle }} — {{ env('APP_NAME') }}</title>
    @if ($metaDesc)
        <meta name="description" content="{{ $metaDesc }}">
    @endif
    <meta property="og:title" content="{{ $metaTitle }}">
    @if ($metaDesc)
        <meta property="og:description" content="{{ $metaDesc }}">
    @endif
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ $metaUrl }}">
    @if ($metaImage)
        <meta property="og:image" content="{{ $metaImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    @if ($metaDesc)
        <meta name="twitter:description" content="{{ $metaDesc }}">
    @endif
    @if ($metaImage)
        <meta name="twitter:image" content="{{ $metaImage }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $orgAnalytics = $c->organization?->meta_json['analytics'] ?? [];
        $fbPixelId = $orgAnalytics['facebook_pixel_id'] ?? null;
        $gtmId = $orgAnalytics['gtm_id'] ?? null;
    @endphp
    @include('partials.gtm-head', ['gtmId' => $gtmId])
    @if (!empty($fbPixelId))
        <script>
            !function (f, b, e, v, n, t, s) {
                if (f.fbq) return; n = f.fbq = function () {
                    n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                }; if (!f._fbq) f._fbq = n;
                n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0;
                t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s)
            }(window, document, 'script',
                'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $fbPixelId }}');
            fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
                 src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1" /></noscript>
    @endif
    <style>
        /* Custom padding wrapper as requested */
        @media (min-width: 1024px) {

            /* lg */
            .content-wrap {
                padding-top: 20px;
                padding-bottom: 20px;
            }
        }
    </style>
</head>

<body class="bg-white text-gray-900 storefront-fixed">
    @include('partials.gtm-body', ['gtmId' => $gtmId])
    <header class="bg-white border-b border-gray-200">
        <div class="mx-auto max-w-7xl px-4 py-3 flex items-center gap-4">
            <a href="{{ route('home') }}" class="text-sky-600 hover:text-sky-700">← Kembali</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl mb-4 pb-20">
        <div class="content-wrap">
            @php
                // Use new cover_path first, fallback to old media system
                $mSorted = $c->media->sortBy('sort_order');
                $coverDesktop = optional($mSorted->firstWhere('platform', 'desktop'))->url;
                $coverMobile = optional($mSorted->firstWhere('platform', 'mobile'))->url;
                $mediaCover = $coverDesktop ?: ($coverMobile ?: optional($mSorted->first())->url);
                $cover = $c->cover_url ?: $mediaCover;
                $progress = (float) $c->target_amount > 0 ? min(100, round(((float) $c->raised_amount / (float) $c->target_amount) * 100)) : 0;
                $donorCount = \App\Models\Donation::where('campaign_id', $c->id)->where('status', 'paid')->count();
                $daysLeft = $c->end_date ? max(0, (int) now()->diffInDays($c->end_date, false)) : null;
            @endphp

            {{-- Hero Banner --}}
            @if ($cover)
                <div class="relative w-full overflow-hidden rounded-xl shadow-lg">
                    <img src="{{ $cover }}" alt="{{ $c->title }}"
                         class="w-full h-[240px] sm:h-[320px] lg:h-[400px] object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-5 sm:p-8">
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white drop-shadow-lg leading-tight">
                            {{ $c->title }}
                        </h1>
                        @if ($c->organization)
                            <x-org-badge :org="$c->organization" size="md" color="white" />
                        @endif
                        @if ($c->summary)
                            <p class="mt-2 text-sm sm:text-base text-white/90 line-clamp-2 max-w-2xl">{{ $c->summary }}</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="relative w-full overflow-hidden rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 shadow-lg">
                    <div class="p-8 sm:p-12 lg:p-16">
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white leading-tight">
                            {{ $c->title }}
                        </h1>
                        @if ($c->organization)
                            <x-org-badge :org="$c->organization" size="md" color="white" />
                        @endif
                        @if ($c->summary)
                            <p class="mt-2 text-sm sm:text-base text-white/90 line-clamp-2 max-w-2xl">{{ $c->summary }}</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Stats & Progress Card --}}
            <div class="relative -mt-6 mx-3 sm:mx-6 rounded-xl bg-white p-5 sm:p-6 shadow-lg ring-1 ring-gray-100">
                {{-- Progress Bar --}}
                <div class="mb-4">
                    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-amber-400 via-amber-500 to-orange-500 transition-all duration-700"
                             style="width: {{ $progress }}%"></div>
                    </div>
                </div>

                {{-- Stats Grid --}}
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-lg sm:text-xl font-bold text-amber-600">
                            Rp {{ number_format((float) $c->raised_amount, 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-500 mt-0.5">
                            terkumpul dari Rp {{ number_format((float) $c->target_amount, 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-lg sm:text-xl font-bold text-gray-800">{{ $donorCount }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">donatur</div>
                    </div>
                    <div>
                        @if ($daysLeft !== null)
                            <div class="text-lg sm:text-xl font-bold text-gray-800">{{ $daysLeft }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">hari lagi</div>
                        @else
                            <div class="text-lg sm:text-xl font-bold text-emerald-600">∞</div>
                            <div class="text-xs text-gray-500 mt-0.5">tanpa batas</div>
                        @endif
                    </div>
                </div>

                {{-- CTA Button --}}
                <div class="mt-5">
                    <a href="{{ route('campaign.donate.form', $c->slug) }}"
                       class="block w-full rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-3.5 text-center text-base font-bold text-white shadow-md hover:from-amber-600 hover:to-orange-600 hover:shadow-lg transition-all duration-200">
                        ❤️ Donasi Sekarang
                    </a>
                </div>
            </div>

            <div class="mt-6 px-4">
                <div class="space-y-4">
                    {{-- Tabs --}}
                    @php $activeTab = $tab ?? 'detail'; @endphp
                    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
                        <div class="border-b border-gray-200">
                            <nav class="flex" aria-label="Tabs">
                                @php
                                    $tabs = [
                                        'detail' => 'Detail',
                                        'laporan' => 'Laporan',
                                        'donatur' => 'Donatur',
                                    ];
                                @endphp
                                @foreach ($tabs as $key => $label)
                                    <a href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}"
                                       class="flex-1 text-center whitespace-nowrap px-4 py-3 text-sm font-medium transition-colors {{ $activeTab === $key ? 'border-b-2 border-amber-500 text-amber-700' : 'text-gray-500 hover:text-amber-600' }}">{{ $label }}</a>
                                @endforeach
                            </nav>
                        </div>

                        <div class="p-4 sm:p-5">
                            @if ($activeTab === 'detail')
                                @if ($c->categories->count())
                                    <div class="mb-4 flex flex-wrap gap-2">
                                        @foreach ($c->categories as $cat)
                                            <a href="{{ route('home', ['category' => $cat->slug]) }}"
                                               class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200">#{{ $cat->name }}</a>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($c->description_md)
                                    <article class="prose max-w-none text-gray-700">
                                        {!! nl2br(e($c->description_md)) !!}
                                    </article>
                                @elseif ($c->summary)
                                    <p class="text-gray-700 leading-relaxed">{{ $c->summary }}</p>
                                @else
                                    <p class="text-gray-400 italic">Belum ada deskripsi untuk campaign ini.</p>
                                @endif
                            @elseif ($activeTab === 'laporan')
                                @if ($articles->count() === 0)
                                    <div class="text-center py-8">
                                        <p class="text-gray-500">Belum ada laporan penyaluran.</p>
                                    </div>
                                @else
                                    <div class="space-y-4">
                                        @foreach ($articles as $a)
                                            <div class="border-l-3 border-amber-400 pl-4">
                                                <div class="text-xs text-gray-400 mb-1">
                                                    {{ optional($a->published_at)->format('d M Y') ?? '—' }}</div>
                                                <h3 class="text-sm font-semibold">
                                                    <a class="text-amber-700 hover:underline"
                                                       href="{{ route('article.show', ['id' => $a->id, 'slug' => \Illuminate\Support\Str::slug($a->title)]) }}">{{ $a->title }}</a>
                                                </h3>
                                                @if ($a->payout?->amount)
                                                    <span class="inline-block mt-1 rounded-full bg-orange-50 px-2 py-0.5 text-xs text-orange-700">
                                                        Rp {{ number_format((float) $a->payout->amount, 0, ',', '.') }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-4">{{ $articles->links() }}</div>
                                @endif
                            @elseif ($activeTab === 'donatur')
                                @if ($donations->count() === 0)
                                    <div class="text-center py-8">
                                        <p class="text-gray-500">Belum ada donatur. Jadilah yang pertama! 💛</p>
                                    </div>
                                @else
                                    <ul class="divide-y divide-gray-100">
                                        @foreach ($donations as $d)
                                            @php
                                                $displayName = $d->is_anonymous ? 'Hamba Allah' : ($d->donor_name ?: '—');
                                                $parts = preg_split('/\s+/', trim($displayName));
                                                $initials = '';
                                                foreach ($parts as $p) {
                                                    if ($p !== '') { $initials .= mb_substr($p, 0, 1); if (mb_strlen($initials) >= 2) break; }
                                                }
                                                $initials = mb_strtoupper($initials ?: 'NA');
                                            @endphp
                                            <li class="flex items-center gap-3 py-3">
                                                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">
                                                    {{ $initials }}</div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="text-sm font-semibold text-gray-900">{{ $displayName }}</div>
                                                        <div class="text-sm font-bold text-amber-600 whitespace-nowrap">Rp {{ number_format((float) $d->amount, 0, ',', '.') }}</div>
                                                    </div>
                                                    <div class="text-xs text-gray-400">{{ optional($d->paid_at)->diffForHumans() }}</div>
                                                    @if (!empty($d->message))
                                                        <div class="mt-1 text-xs text-gray-500 italic">"{{ $d->message }}"</div>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="mt-4">{{ $donations->links() }}</div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    @php
        $shareUrlFab = route('campaign.show', $c->slug);
        $shareTextFab = $c->title;
        $encUrlFab = urlencode($shareUrlFab);
        $encTextFab = urlencode($shareTextFab);
    @endphp
    <div id="fab-share-bar" class="fixed inset-x-0 bottom-0 z-30 bg-white border-t border-gray-200 shadow-lg">
        <div class="mx-auto max-w-7xl px-4 py-3 flex items-center gap-3">
            <button type="button" id="fab-share-trigger"
                    class="inline-flex items-center justify-center rounded-lg border border-orange-400 bg-white px-5 py-2.5 text-sm font-semibold text-orange-600 hover:bg-orange-50 transition-colors"
                    aria-label="Bagikan">Bagikan</button>
            <a href="{{ route('campaign.donate.form', $c->slug) }}"
               class="flex-1 inline-flex items-center justify-center rounded-lg bg-orange-500 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-orange-600 transition-colors">Donasi</a>

            <div id="fab-share-popover"
                 class="pointer-events-auto invisible absolute bottom-14 left-4 right-4 translate-y-2 opacity-0 transition-all duration-150 ease-out">
                <div class="flex items-center justify-center gap-3 rounded-xl bg-white p-3 shadow-lg ring-1 ring-gray-200">
                    <a href="https://wa.me/?text={{ $encTextFab }}%20{{ $encUrlFab }}" target="_blank" rel="noopener"
                       class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#25D366] text-white hover:opacity-90"
                       aria-label="WhatsApp">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                            <path d="M20.52 3.48A11.93 11.93 0 0012.05 0C5.53.02.25 5.29.27 11.81a11.76 11.76 0 001.64 6.07L0 24l6.3-1.82a11.86 11.86 0 005.73 1.49h.01c6.52 0 11.8-5.27 11.82-11.79a11.8 11.8 0 00-3.34-8.4z" />
                        </svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encUrlFab }}" target="_blank" rel="noopener"
                       class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#1877F2] text-white hover:opacity-90"
                       aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                            <path d="M13.5 2.25a6.75 6.75 0 00-6.75 6.75v2.25H4.5a.75.75 0 00-.75 .75v3a.75.75 0 00.75 .75h2.25V21a.75.75 0 00.75 .75h3a.75.75 0 00.75 -.75v-5.25H14.6a.75.75 0 00.74 -.63l.38-3a.75.75 0 00-.74-.87H11.25V9a2.25 2.25 0 012.25 -2.25H15a.75.75 0 00.75 -.75V3a.75.75 0 00-.75 -.75h-1.5z" />
                        </svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?text={{ $encTextFab }}&url={{ $encUrlFab }}" target="_blank" rel="noopener"
                       class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-black text-white hover:opacity-90"
                       aria-label="X">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                            <path d="M18.244 2H21l-6.52 7.455L22 22h-6.828l-5.34-7.027L3.6 22H1l7.035-8.04L2 2h6.914l4.83 6.42L18.244 2zm-2.392 18h1.662L7.225 4H5.47l10.382 16z" />
                        </svg>
                    </a>
                    <a href="https://t.me/share/url?url={{ $encUrlFab }}&text={{ $encTextFab }}" target="_blank" rel="noopener"
                       class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#229ED9] text-white hover:opacity-90"
                       aria-label="Telegram">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                            <path d="M9.04 15.36l-.37 5.2c.53 0 .76-.23 1.03-.5l2.48-2.38 5.14 3.77c.94 .52 1.6 .25 1.84 -.87l3.34 -15.71h.01c.3 -1.42 -.51 -1.98 -1.43 -1.63L1.4 9.93C.02 10.48 .04 11.3 1.14 11.64l5.2 1.62 12.06 -7.6c.57 -.35 1.1 -.16 .67 .22L9.04 15.36z" />
                        </svg>
                    </a>
                    <button type="button" id="fab-copy-link"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gray-600 text-white hover:opacity-90"
                            aria-label="Copy Link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.702a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.243 8.81" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Media preview modal
        (function () {
            const modal = document.createElement('div');
            modal.id = 'media-modal';
            modal.className = 'fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4';
            modal.style = 'background: #000000a1;';
            modal.innerHTML = `
                <div class="relative max-w-5xl w-full">
                    <button type="button" id="media-close" class="absolute -top-10 right-0 text-white/80 hover:text-white text-2xl" aria-label="Tutup">×</button>
                    <button type="button" id="media-prev" class="absolute left-0 top-1/2 -translate-y-1/2 rounded-full bg-white/20 p-2 text-white hover:bg-white/30" aria-label="Sebelumnya">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
                    </button>
                    <button type="button" id="media-next" class="absolute right-0 top-1/2 -translate-y-1/2 rounded-full bg-white/20 p-2 text-white hover:bg-white/30" aria-label="Berikutnya">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg>
                    </button>
                    <img id="media-img" alt="Preview" class="mx-auto max-h-[80vh] w-auto rounded shadow-lg" />
                </div>
            `;
            document.body.appendChild(modal);

            const imgEl = modal.querySelector('#media-img');
            const closeBtn = modal.querySelector('#media-close');
            const prevBtn = modal.querySelector('#media-prev');
            const nextBtn = modal.querySelector('#media-next');

            const items = Array.from(document.querySelectorAll('.js-media-click'));
            let current = -1;

            function openAt(i) {
                if (!items.length) return;
                current = (i + items.length) % items.length;
                const el = items[current];
                const chosen = (el.currentSrc && el.currentSrc !== '') ? el.currentSrc : el.getAttribute('src');
                imgEl.src = chosen;
                imgEl.alt = el.getAttribute('alt') || 'Preview';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
            function close() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
                current = -1;
            }
            function next() { openAt(current + 1); }
            function prev() { openAt(current - 1); }

            items.forEach((el, idx) => {
                el.addEventListener('click', (e) => {
                    e.preventDefault();
                    openAt(idx);
                });
            });

            closeBtn.addEventListener('click', close);
            modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
            nextBtn.addEventListener('click', (e) => { e.stopPropagation(); next(); });
            prevBtn.addEventListener('click', (e) => { e.stopPropagation(); prev(); });
            document.addEventListener('keydown', (e) => {
                if (modal.classList.contains('hidden')) return;
                if (e.key === 'Escape') close();
                if (e.key === 'ArrowRight') next();
                if (e.key === 'ArrowLeft') prev();
            });
        })();
        // Floating share toggle (bottom bar)
        (function () {
            const trigger = document.getElementById('fab-share-trigger');
            const pop = document.getElementById('fab-share-popover');
            const openPop = () => { if (!pop) return; pop.classList.remove('opacity-0', 'invisible', 'translate-y-2'); };
            const closePop = () => { if (!pop) return; pop.classList.add('opacity-0', 'invisible', 'translate-y-2'); };
            trigger && trigger.addEventListener('click', (e) => { e.preventDefault(); if (pop.classList.contains('invisible')) openPop(); else closePop(); });
            document.addEventListener('click', (e) => { if (!pop || !trigger) return; if (pop.contains(e.target) || trigger.contains(e.target)) return; closePop(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });
        })();
        document.querySelectorAll('.preset-amount').forEach(btn => {
            btn.addEventListener('click', () => {
                const val = btn.getAttribute('data-amount');
                const input = document.getElementById('amount-input');
                if (input) input.value = val;
            });
        });
        // Popover share toggle
        const trigger = document.getElementById('share-trigger');
        const pop = document.getElementById('share-popover');
        const openPop = () => {
            if (!pop) return;
            pop.classList.remove('opacity-0', 'invisible', 'translate-y-2');
        };
        const closePop = () => {
            if (!pop) return;
            pop.classList.add('opacity-0', 'invisible', 'translate-y-2');
        };
        trigger && trigger.addEventListener('click', (e) => {
            e.preventDefault();
            if (pop.classList.contains('invisible')) openPop(); else closePop();
        });
        document.addEventListener('click', (e) => {
            if (!pop || !trigger) return;
            if (pop.contains(e.target) || trigger.contains(e.target)) return;
            closePop();
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });

        // Copy link button
        (function () {
            const copyBtn = document.getElementById('fab-copy-link');
            if (!copyBtn) return;
            copyBtn.addEventListener('click', () => {
                navigator.clipboard.writeText('{{ route('campaign.show', $c->slug) }}').then(() => {
                    copyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>';
                    setTimeout(() => {
                        copyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.702a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.243 8.81" /></svg>';
                    }, 1500);
                });
            });
        })();
    </script>
</body>

</html>
