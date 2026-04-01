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

    <main class="mx-auto max-w-7xl mb-4">
        <div class="content-wrap">
            @php
                $mSorted = $c->media->sortBy('sort_order');
                $coverDesktop = optional($mSorted->firstWhere('platform', 'desktop'))->url;
                $coverMobile = optional($mSorted->firstWhere('platform', 'mobile'))->url;
                $cover = $coverDesktop ?: ($coverMobile ?: optional($mSorted->first())->url);
            @endphp
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-4">
                    @if ($cover)
                        <div class="w-full overflow-hidden">
                            <picture>
                                @if ($coverMobile)
                                    <source media="(max-width: 768px)" srcset="{{ $coverMobile }}">
                                @endif
                                <img src="{{ $cover }}" alt="{{ $c->title }}"
                                     class="w-full object-cover shadow js-media-click cursor-zoom-in" />
                            </picture>
                            <div class="bg-white p-5 shadow">
                                @php
                                    $progress = (float) $c->target_amount > 0 ? min(100, round(((float) $c->raised_amount / (float) $c->target_amount) * 100)) : 0;
                                @endphp
                                <div class="mb-3 space-y-2">
                                    <h1 class="text-2xl font-bold leading-tight">{{ $c->title }}</h1>

                                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                                        <div class="h-full bg-sky-500" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-gray-600">
                                        <span>Terkumpul: Rp
                                            {{ number_format((float) $c->raised_amount, 2, ',', '.') }}</span>
                                        <span>Target: Rp {{ number_format((float) $c->target_amount, 2, ',', '.') }}</span>
                                    </div>
                                </div>

                                <h2 class="mb-1 text-lg font-semibold">Dukung Program Ini</h2>
                                <p class="text-sm text-gray-600">Klik tombol Donasi untuk melanjutkan ke halaman donasi.</p>

                                <div class="mt-2 space-y-3">
                                    <div class="relative inline-block w-full">
                                        <a href="{{ route('campaign.donate.form', $c->slug) }}"
                                           class="flex-1 inline-flex items-center justify-center rounded-md bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-orange-600 w-full">Donasi
                                            Sekarang</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Tabs -->
                    @php $activeTab = $tab ?? 'detail'; @endphp
                    <div class="bg-white mb-5" style="margin-bottom: 50px !important;margin-top: -15px;">
                        <div class="border-b border-gray-200">
                            <nav class="flex overflow-x-auto" aria-label="Tabs">
                                @php
                                    $tabs = [
                                        'detail' => 'Detail',
                                        'laporan' => 'Laporan',
                                        'donatur' => 'Donatur',
                                    ];
                                @endphp
                                @foreach ($tabs as $key => $label)
                                    <a href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}"
                                       class="whitespace-nowrap px-4 py-3 text-sm {{ $activeTab === $key ? 'border-b-2 border-sky-600 font-medium text-sky-700' : 'text-gray-600 hover:text-sky-700 hover:border-b-2 hover:border-sky-300' }}">{{ $label }}</a>
                                @endforeach
                            </nav>
                        </div>

                        <div class="p-5">
                            @if ($activeTab === 'detail')
                                @if ($c->categories->count())
                                    <div class="mb-3 flex flex-wrap gap-2">
                                        @foreach ($c->categories as $cat)
                                            <a href="{{ route('home', ['category' => $cat->slug]) }}"
                                               class="rounded-full bg-sky-50 px-2 py-1 text-xs text-sky-700 ring-1 ring-sky-200">#{{ $cat->name }}</a>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- @if ($c->media->count() > 1)
                                <div class="mt-4 mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    @foreach ($mSorted->values()->skip(1) as $m)
                                    <img src="{{ $m->url }}"
                                         class="aspect-video w-full rounded-lg object-cover js-media-click cursor-zoom-in"
                                         alt="media" />
                                    @endforeach
                                </div>
                                @endif --}}

                                @if ($c->summary)
                                    <p class="mb-3 text-gray-700">{{ $c->summary }}</p>
                                @endif
                                @if ($c->description_md)
                                    <article class="prose max-w-none">
                                        {!! nl2br(e($c->description_md)) !!}
                                    </article>
                                @endif
                            @elseif ($activeTab === 'laporan')
                                @if ($articles->count() === 0)
                                    <p class="text-gray-600">Belum ada laporan.</p>
                                @else
                                    <ol class="relative ml-3 border-l border-gray-200">
                                        @foreach ($articles as $a)
                                            <li class="mb-8 ml-6">
                                                <span
                                                      class="absolute -left-3 mt-1 h-6 w-6 rounded-full bg-sky-100 text-sky-600 ring-2 ring-white">
                                                </span>
                                                <div class="mb-1 text-xs text-gray-500">
                                                    {{ optional($a->published_at)->format('d M Y') ?? '—' }}</div>
                                                <h3 class="mb-2 text-base font-semibold">
                                                    <a class="text-sky-700 hover:underline"
                                                       href="{{ route('article.show', ['id' => $a->id, 'slug' => \Illuminate\Support\Str::slug($a->title)]) }}">{{ $a->title }}</a>
                                                </h3>
                                                @if ($a->payout?->amount)
                                                    <div
                                                         class="mb-2 inline-block rounded-full bg-orange-50 px-2 py-1 text-xs text-orange-700 ring-1 ring-orange-200">
                                                        Anggaran: Rp {{ number_format((float) $a->payout->amount, 2, ',', '.') }}</div>
                                                @endif
                                                @if ($a->body_md)
                                                    <div class="prose max-w-none text-gray-700">
                                                        {{ \Illuminate\Support\Str::limit(strip_tags($a->body_md), 280) }}
                                                    </div>
                                                    <div class="mt-2">
                                                        <a class="text-sm text-sky-600 hover:underline"
                                                           href="{{ route('article.show', ['id' => $a->id, 'slug' => \Illuminate\Support\Str::slug($a->title)]) }}">Baca
                                                            selengkapnya →</a>
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                    <div class="mt-4">{{ $articles->links() }}</div>
                                @endif
                            @elseif ($activeTab === 'donatur')
                                @if ($donations->count() === 0)
                                    <p class="text-gray-600">Belum ada donatur.</p>
                                @else
                                    <ul class="divide-y divide-gray-100 bg-white">
                                        @foreach ($donations as $d)
                                            @php
                                                $displayName = $d->is_anonymous ? 'Hamba Allah' : ($d->donor_name ?: '—');
                                                $parts = preg_split('/\s+/', trim($displayName));
                                                $initials = '';
                                                foreach ($parts as $p) {
                                                    if ($p !== '') {
                                                        $initials .= mb_substr($p, 0, 1);
                                                        if (mb_strlen($initials) >= 2)
                                                            break;
                                                    }
                                                }
                                                $initials = mb_strtoupper($initials ?: 'NA');
                                            @endphp
                                            <li class="flex items-center gap-3 py-3">
                                                <div
                                                     class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-700">
                                                    {{ $initials }}</div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div>
                                                            <div class="text-sm font-semibold text-gray-900">{{ $displayName }}
                                                            </div>
                                                            <div class="text-xs text-gray-500">
                                                                {{ optional($d->paid_at)->diffForHumans() }}</div>
                                                        </div>
                                                        <div class="text-sm font-semibold text-gray-900 whitespace-nowrap">Rp
                                                            {{ number_format((float) $d->amount, 0, ',', '.') }}</div>
                                                    </div>
                                                    @if (!empty($d->message))
                                                            <div class="mt-1 text-md font-bold text-gray-700 break-words">"{{ $d->message }}"</div>
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

                <!-- Desktop-only Aside: Related Programs -->
                <aside class="hidden lg:block">
                    @if (($related ?? collect())->isNotEmpty())
                        <div class="sticky top-4 space-y-4">
                            <div class="rounded-md bg-white p-5 shadow">
                                <h2 class="mb-3 text-lg font-semibold">Program Lainnya</h2>
                                <div class="space-y-4">
                                    @foreach ($related as $r)
                                        @php
                                            $rm = $r->media->sortBy('sort_order');
                                            $rCoverDesktop = optional($rm->firstWhere('platform', 'desktop'))->url;
                                            $rCoverMobile = optional($rm->firstWhere('platform', 'mobile'))->url;
                                            $rCover = $rCoverDesktop ?: ($rCoverMobile ?: optional($rm->first())->url);
                                            $rTarget = (float) ($r->target_amount ?? 0);
                                            $rRaised = (float) ($r->raised_amount ?? 0);
                                            $rProgress = $rTarget > 0 ? min(100, round(($rRaised / max(1, $rTarget)) * 100)) : 0;
                                        @endphp
                                        <a href="{{ route('campaign.show', $r->slug) }}"
                                           class="group block rounded-md border border-gray-200 hover:border-sky-300 hover:shadow-sm overflow-hidden">
                                            @if ($rCover)
                                                <img src="{{ $rCover }}" alt="{{ $r->title }}" class="w-full object-cover" />
                                            @endif
                                            <div class="p-3">
                                                <div
                                                     class="line-clamp-2 text-sm font-medium text-gray-900 group-hover:text-sky-700">
                                                    {{ $r->title }}</div>
                                                <div class="mt-2 space-y-1">
                                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                                        <div class="h-full bg-sky-500" style="width: {{ $rProgress }}%"></div>
                                                    </div>
                                                    <div class="flex items-center justify-between text-xs text-gray-600">
                                                        <span>Terkumpul: Rp {{ number_format($rRaised, 0, ',', '.') }}</span>
                                                        <span>Target: Rp {{ number_format($rTarget, 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </main>
    @php
        $shareUrlFab = route('campaign.show', $c->slug);
        $shareTextFab = $c->title;
        $encUrlFab = urlencode($shareUrlFab);
        $encTextFab = urlencode($shareTextFab);
    @endphp
    <div id="fab-share-bar" class="fixed inset-x-0 bottom-0 z-30 bg-white backdrop-blur border-t border-gray-200 transform transition-all duration-200 ease-out translate-y-full opacity-0 pointer-events-none">
        <div class="mx-auto max-w-7xl px-4 py-3 flex items-center gap-3">
            <button type="button" id="fab-share-trigger"
                    class="inline-flex items-center justify-center rounded-md border border-orange-500 bg-white px-4 py-2.5 text-sm font-semibold text-orange-600 shadow-sm hover:bg-orange-50"
                    aria-label="Bagikan">Bagikan</button>
            <a href="{{ route('campaign.donate.form', $c->slug) }}"
               class="flex-1 inline-flex items-center justify-center rounded-md bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-orange-600">Donasi</a>

            <div id="fab-share-popover"
                 class="pointer-events-auto invisible absolute bottom-14 right-4 translate-y-2 opacity-0 transition-all duration-150 ease-out">
                <div class="flex items-center gap-3 rounded-xl bg-white p-2 shadow-lg ring-1 ring-gray-200">
                    <a href="https://wa.me/?text={{ $encTextFab }}%20{{ $encUrlFab }}" target="_blank" rel="noopener"
                       class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#25D366] text-white hover:opacity-90"
                       aria-label="WhatsApp">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                            <path
                                  d="M20.52 3.48A11.93 11.93 0 0012.05 0C5.53.02.25 5.29.27 11.81a11.76 11.76 0 001.64 6.07L0 24l6.3-1.82a11.86 11.86 0 005.73 1.49h.01c6.52 0 11.8-5.27 11.82-11.79a11.8 11.8 0 00-3.34-8.4z" />
                        </svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encUrlFab }}" target="_blank"
                       rel="noopener"
                       class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#1877F2] text-white hover:opacity-90"
                       aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                            <path
                                  d="M13.5 2.25a6.75 6.75 0 00-6.75 6.75v2.25H4.5a.75.75 0 00-.75 .75v3a.75.75 0 00.75 .75h2.25V21a.75.75 0 00.75 .75h3a.75.75 0 00.75 -.75v-5.25H14.6a.75.75 0 00.74 -.63l.38-3a.75.75 0 00-.74-.87H11.25V9a2.25 2.25 0 012.25 -2.25H15a.75.75 0 00.75 -.75V3a.75.75 0 00-.75 -.75h-1.5z" />
                        </svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?text={{ $encTextFab }}&url={{ $encUrlFab }}"
                       target="_blank" rel="noopener"
                       class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-black text-white hover:opacity-90"
                       aria-label="X">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                            <path
                                  d="M18.244 2H21l-6.52 7.455L22 22h-6.828l-5.34-7.027L3.6 22H1l7.035-8.04L2 2h6.914l4.83 6.42L18.244 2zm-2.392 18h1.662L7.225 4H5.47l10.382 16z" />
                        </svg>
                    </a>
                    <a href="https://t.me/share/url?url={{ $encUrlFab }}&text={{ $encTextFab }}" target="_blank"
                       rel="noopener"
                       class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#229ED9] text-white hover:opacity-90"
                       aria-label="Telegram">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                            <path
                                  d="M9.04 15.36l-.37 5.2c.53 0 .76-.23 1.03-.5l2.48-2.38 5.14 3.77c.94 .52 1.6 .25 1.84 -.87l3.34 -15.71h.01c.3 -1.42 -.51 -1.98 -1.43 -1.63L1.4 9.93C.02 10.48 .04 11.3 1.14 11.64l5.2 1.62 12.06 -7.6c.57 -.35 1.1 -.16 .67 .22L9.04 15.36z" />
                        </svg>
                    </a>
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

        // Show bottom bar only after scrolling down a bit
        (function () {
            const bar = document.getElementById('fab-share-bar');
            if (!bar) return;
            const update = () => {
                const y = window.scrollY || window.pageYOffset || 0;
                if (y > 200) {
                    bar.classList.remove('translate-y-full', 'opacity-0', 'pointer-events-none');
                } else {
                    bar.classList.add('translate-y-full', 'opacity-0', 'pointer-events-none');
                }
            };
            update();
            window.addEventListener('scroll', update, { passive: true });
        })();
    </script>
</body>

</html>
