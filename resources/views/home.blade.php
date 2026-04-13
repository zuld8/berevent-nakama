@extends('layouts.storefront')

@php
    $appName        = config('app.name', 'Nakama Project Hub');
    $siteUrl        = config('app.url', url('/'));
    $homeUrl        = route('home');
    $siteName       = $org->name ?? $appName;
    $metaDesc       = 'Platform event, workshop, seminar, dan komunitas online & offline terpercaya di Indonesia. Daftar tiket, akses rekaman eksklusif, dan download materi di ' . $siteName . '.';
    $hasHero        = isset($heroes) && $heroes->count() > 0;
    $firstHeroImage = $hasHero ? optional($heroes->first())->image_url : null;
    $ogImage        = $org->logo_url ?? $firstHeroImage ?? null;
    $soc            = $org->social_json ?? [];
    $sameAs         = array_values(array_filter([
        $soc['instagram'] ?? null,
        $soc['facebook']  ?? null,
        $soc['twitter']   ?? ($soc['x'] ?? null),
        $soc['youtube']   ?? null,
        $soc['linkedin']  ?? null,
        $soc['tiktok']    ?? null,
    ]));

    // Analytics
    $analytics  = $org?->meta_json['analytics'] ?? [];
    $fbPixelId  = $analytics['facebook_pixel_id'] ?? null;
    $gtmId      = $analytics['gtm_id'] ?? null;
@endphp

{{-- ── SEO sections ───────────────────────────────────────────────────────── --}}
@section('seo_title',       $siteName . ' — Event, Workshop & Komunitas Indonesia')
@section('seo_description', $metaDesc)
@section('seo_canonical',   $homeUrl)
@section('og_title',        $siteName . ' — Event & Workshop')
@section('og_description',  $metaDesc)
@section('og_image',        $ogImage ?? '')

@push('json_ld')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type'    => 'Organization',
    'name'     => $siteName,
    'url'      => $siteUrl,
    'logo'     => $ogImage,
    'sameAs'   => !empty($sameAs) ? $sameAs : null,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@push('head')
    <meta name="keywords" content="event online, workshop, seminar, tiket event, komunitas, nakama, Indonesia" />
    @include('partials.gtm-head', ['gtmId' => $gtmId])
    @if (!empty($fbPixelId))
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
            n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
            document,'script','https://connect.facebook.net/en_US/fbevents.js');
            fbq('init','{{ $fbPixelId }}');fbq('track','PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1"/></noscript>
    @endif
@endpush

@section('content')
    @include('partials.gtm-body', ['gtmId' => $gtmId])
    <main class="mx-auto max-w-7xl px-4 py-4">
        <section class="mb-3">
            @include('partials.header-user', [
                'user' => auth()->user(),
                'location' => $org?->name ?? 'Location',
                'notif' => 0,
                'query' => $q ?? '',
                'org' => $org ?? null,
            ])
        </section>
        <section class="mb-3">
            <!-- Hero slider -->
            <div class="relative rounded-xl shadow">
                <div id="hero-track" class="flex snap-x snap-mandatory overflow-x-auto overflow-hidden scroll-smooth rounded-xl">
                    @php
                        $hasHero = isset($heroes) && $heroes->count() > 0;
                    @endphp
                    @if ($hasHero)
                        @foreach ($heroes as $h)
                            @php
                                $img = $h->image_url;
                                $title = $h->event->title ?? 'Event';
                                $href = route('event.index');
                            @endphp
                            <a href="{{ $href }}"
                               class="w-full flex-shrink-0 snap-start block">
                                @if ($img)
                                    <img src="{{ $img }}" class="h-full w-full object-cover rounded-xl" alt="{{ $title }}" />
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-gray-100 text-gray-400">
                                        {{ $title }}</div>
                                @endif
                            </a>
                        @endforeach
                    @else
                        <div class="flex h-56 rounded-xl w-full items-center justify-center bg-gray-100 text-gray-400 md:h-72">Banner
                        </div>
                    @endif
                </div>
                @if ($hasHero && $heroes->count() > 1)
                    @php $totalHeroes = $heroes->count(); @endphp
                    <div id="hero-dots"
                         class="pointer-events-auto absolute bottom-2 left-0 right-0 flex justify-center gap-2">
                        @for ($i = 0; $i < $totalHeroes; $i++)
                            <button type="button" class="h-2 w-2 rounded-full bg-white/60 hover:bg-white ring-1 ring-black/10"
                                    data-hero-dot="{{ $i }}" aria-label="Slide {{ $i + 1 }}"></button>
                        @endfor
                    </div>
                    <button type="button" onclick="heroPrev()"
                            class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-2 shadow hover:bg-white"
                            aria-label="Sebelumnya">‹</button>
                    <button type="button" onclick="heroNext()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-2 shadow hover:bg-white"
                            aria-label="Berikutnya">›</button>
                @endif
            </div>

            <!-- Categories carousel -->
            <div class="mt-3">
                <x-categories :items="$categoryItems" :active="$activeCategory" :q="$q" title="Kategori" :seeAllHref="route('event.index')" />
            </div>
        </section>

        @if (($events ?? collect())->count() === 0)
            <div class="rounded-md border border-gray-200 bg-white p-6 text-center text-gray-600 mb-5">Belum ada event.</div>
        @else
            <div class="flex items-center justify-between gap-4 mb-3">
                <h2 class="text-sm font-semi">Rekomendasi Untuk Kamu</h2>
            </div>
            <div id="event-grid" class="grid grid-cols-1 gap-3 mb-5">
                @include('partials.event-cards', ['events' => $events])
            </div>
            <template id="event-skeleton-template">
                <div class="animate-pulse overflow-hidden rounded-xl border border-gray-200 bg-white p-3">
                    <div class="flex items-start gap-3">
                        <div class="h-16 w-24 rounded-lg bg-gray-200"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                            <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                        </div>
                    </div>
                </div>
            </template>

            <div class="mt-3 flex justify-center mb-5">
                <button id="load-more-btn"
                        data-next-page="{{ $events->hasMorePages() ? ($events->currentPage() + 1) : '' }}"
                        data-has-more="{{ $events->hasMorePages() ? '1' : '0' }}"
                        class="{{ $events->hasMorePages() ? 'hidden' : 'hidden' }} inline-flex items-center rounded-full bg-sky-600 px-3 py-1 text-xs font-medium text-white shadow hover:bg-sky-700 btn-sm">Muat lebih banyak</button>
            </div>

            <div id="load-more-sentinel" class="h-8 w-full"></div>
        @endif


        @if (($campaigns ?? collect())->isNotEmpty())
            <section class="mt-4 mb-5">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Campaign Donasi</h2>
                </div>
                <div class="space-y-3">
                    @foreach ($campaigns as $c)
                        @php
                            $target = (float) $c->target_amount;
                            $raised = (float) $c->raised_amount;
                            $pct = $target > 0 ? min(100, round(($raised / $target) * 100)) : 0;
                            $daysLeft = $c->end_date ? max(0, (int) now()->diffInDays($c->end_date, false)) : null;
                        @endphp
                        <a href="{{ route('campaign.show', $c->slug) }}"
                           class="group block overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md hover:border-amber-200 transition-all duration-200">
                            <div class="flex items-start gap-3 p-3">
                                {{-- Thumbnail --}}
                                <div class="relative h-24 w-24 flex-shrink-0 overflow-hidden rounded-lg">
                                    @if ($c->cover_url)
                                        <img src="{{ $c->cover_url }}" alt="{{ $c->title }}"
                                             class="h-full w-full object-cover" loading="lazy" />
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-amber-100 to-orange-100">
                                            <svg class="h-8 w-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-bold text-gray-900 group-hover:text-amber-600 line-clamp-1 transition-colors">
                                        {{ $c->title }}
                                    </h3>
                                    @if ($c->organization)
                                        <span class="inline-flex items-center gap-1 text-[11px] text-gray-400 mt-0.5">
                                            @if ($c->organization->logo_url)
                                                <img src="{{ $c->organization->logo_url }}" alt="" class="h-3.5 w-3.5 rounded-full object-cover ring-1 ring-black/10" />
                                            @endif
                                            by {{ $c->organization->name }}
                                            @if ($c->organization->is_verified)
                                                <svg class="h-3 w-3 text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                        </span>
                                    @endif
                                    @if ($c->summary)
                                        <p class="mt-0.5 text-xs text-gray-500 line-clamp-1">{{ $c->summary }}</p>
                                    @endif

                                    {{-- Progress bar --}}
                                    <div class="mt-2">
                                        <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-orange-500 transition-all duration-500"
                                                 style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>

                                    {{-- Stats row --}}
                                    <div class="mt-1.5 flex items-center gap-3 text-xs">
                                        <span class="font-bold text-amber-600">Rp {{ number_format($raised, 0, ',', '.') }}</span>
                                        <span class="text-gray-300">•</span>
                                        <span class="text-gray-400">Target Rp {{ number_format($target, 0, ',', '.') }}</span>
                                        @if ($daysLeft !== null)
                                            <span class="text-gray-300">•</span>
                                            <span class="text-gray-400">{{ $daysLeft > 0 ? $daysLeft . ' hari' : 'Berakhir' }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if (($latestNews ?? collect())->isNotEmpty())
            <section class="mt-10">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Berita Terbaru</h2>
                    <a href="{{ route('news.index') }}" class="text-sm text-sky-600 hover:underline">Lihat semua</a>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestNews as $n)
                        <a href="{{ route('news.show', $n->slug) }}"
                           class="group block overflow-hidden rounded-md bg-white shadow hover:ring-1 hover:ring-sky-200">
                            <article>
                                @if ($n->cover_url)
                                    <img src="{{ $n->cover_url }}" alt="{{ $n->title }}" class="w-full object-cover" />
                                @endif
                                <div class="p-4">
                                    <h3 class="line-clamp-2 text-base font-semibold text-gray-900 group-hover:text-sky-700">
                                        {{ $n->title }}</h3>
                                    <div class="mt-1 text-xs text-gray-500">{{ optional($n->published_at)->format('d M Y') }}
                                    </div>
                                    @if ($n->excerpt)
                                        <p class="mt-2 line-clamp-3 text-sm text-gray-700">{{ $n->excerpt }}</p>
                                    @endif
                                </div>
                            </article>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    <script>
        // Mobile nav toggle
        (function () {
            const openBtn = document.getElementById('mobile-nav-open');
            const closeBtn = document.getElementById('mobile-nav-close');
            const panel = document.getElementById('mobile-nav');
            const backdrop = document.getElementById('mobile-nav-backdrop');
            const open = () => { if (!panel) return; panel.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); };
            const close = () => { if (!panel) return; panel.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); };
            openBtn && openBtn.addEventListener('click', (e) => { e.preventDefault(); open(); });
            closeBtn && closeBtn.addEventListener('click', (e) => { e.preventDefault(); close(); });
            backdrop && backdrop.addEventListener('click', close);
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
        })();
        // Hero carousel
        const heroTrack = document.getElementById('hero-track');
        let heroSlides = heroTrack ? Array.from(heroTrack.querySelectorAll('a')) : [];
        let heroIndex = 0;
        let heroTimer = null;
        const heroIntervalMs = 5000;

        function heroUpdateDots() {
            const dots = document.querySelectorAll('[data-hero-dot]');
            dots.forEach((d, i) => {
                if (i === heroIndex) {
                    d.classList.add('bg-white');
                    d.classList.remove('bg-white/60');
                } else {
                    d.classList.remove('bg-white');
                    d.classList.add('bg-white/60');
                }
            });
        }

        function heroGo(i) {
            if (!heroTrack || heroSlides.length === 0) return;
            heroIndex = (i + heroSlides.length) % heroSlides.length;
            const target = heroSlides[heroIndex];
            heroTrack.scrollTo({ left: target.offsetLeft, behavior: 'smooth' });
            heroUpdateDots();
        }

        function heroNext() { heroGo(heroIndex + 1); }
        function heroPrev() { heroGo(heroIndex - 1); }

        function heroStart() {
            if (heroTimer || heroSlides.length <= 1) return;
            heroTimer = setInterval(heroNext, heroIntervalMs);
        }
        function heroStop() {
            if (heroTimer) {
                clearInterval(heroTimer);
                heroTimer = null;
            }
        }

        // Init
        if (heroTrack && heroSlides.length > 0) {
            heroUpdateDots();
            heroStart();
            heroTrack.addEventListener('mouseenter', heroStop);
            heroTrack.addEventListener('mouseleave', heroStart);
            window.addEventListener('resize', () => heroGo(heroIndex));
            document.addEventListener('visibilitychange', () => document.hidden ? heroStop() : heroStart());
            // Dots
            document.querySelectorAll('[data-hero-dot]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const i = parseInt(btn.getAttribute('data-hero-dot') || '0', 10);
                    heroStop();
                    heroGo(i);
                    heroStart();
                });
            });
        }

        // Infinite load events
        const loadBtn = document.getElementById('load-more-btn');
        const grid = document.getElementById('event-grid');
        const sentinel = document.getElementById('load-more-sentinel');
        const skeletonTpl = document.getElementById('event-skeleton-template');
        let loading = false;
        let skeletons = [];
        async function loadMore() {
            if (loading) return;
            if (!loadBtn || loadBtn.dataset.hasMore !== '1') return;
            loading = true;
            const nextPage = loadBtn.dataset.nextPage;
            const params = new URLSearchParams({
                page: nextPage,
                perPage: '{{ $perPage ?? 6 }}',
                category: '{{ $activeCategory ?? '' }}',
                q: '{{ $q ?? '' }}',
            });
            // show skeletons
            if (skeletonTpl && grid) {
                skeletons = Array.from({ length: 6 }).map(() => {
                    const node = skeletonTpl.content.firstElementChild.cloneNode(true);
                    grid.appendChild(node);
                    return node;
                });
            }
            try {
                const res = await fetch(`{{ route('home.chunk') }}?${params.toString()}`);
                const data = await res.json();
                if (data?.html) {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = data.html;
                    tmp.childNodes.forEach(n => grid.appendChild(n));
                }
                if (data?.hasMore) {
                    loadBtn.dataset.nextPage = data.nextPage;
                    loadBtn.dataset.hasMore = '1';
                } else {
                    loadBtn.dataset.hasMore = '0';
                }
            } catch (e) {
                console.error(e);
            }
            // remove skeletons
            skeletons.forEach(el => el.remove());
            skeletons = [];
            loading = false;
        }
        loadBtn && loadBtn.addEventListener('click', loadMore);

        if (sentinel && loadBtn && loadBtn.dataset.hasMore === '1') {
            const io = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        loadMore();
                    }
                });
            }, { rootMargin: '200px' });
            io.observe(sentinel);
        }
    </script>
@endsection
