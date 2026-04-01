<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $a->title }} — {{ $c->title }} — {{ env('APP_NAME') }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @php
        $analytics = $org?->meta_json['analytics'] ?? [];
        $gtmId = $analytics['gtm_id'] ?? null;
    @endphp
    @include('partials.gtm-head', ['gtmId' => $gtmId])
    <style>
        /* Hide Trix attachment captions (file names) under images */
        .prose figure.attachment .attachment__caption,
        .prose figure.trix-attachment .attachment__caption { display: none !important; }
        /* Add vertical spacing to images from rich text */
        .prose img { margin-top: 1.25rem; margin-bottom: 1.25rem; }
        .prose figure { margin-top: 1.25rem; margin-bottom: 1.25rem; }
    </style>
</head>
<body class="bg-white text-gray-900 storefront-fixed">
    @include('partials.gtm-body', ['gtmId' => $gtmId])
    <header class="bg-white border-b border-gray-200">
        <div class="mx-auto max-w-7xl px-4 py-3 flex items-center gap-4">
            <a href="{{ route('campaign.show', $c->slug) }}" class="text-sky-600 hover:text-sky-700">← Kembali ke Campaign</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <article class="lg:col-span-2 space-y-3">
            <h1 class="text-2xl font-bold leading-tight">{{ $a->title }}</h1>
            <div class="text-sm text-gray-500">{{ optional($a->published_at)->format('d M Y') ?? '—' }}</div>
            <div class="text-sm text-gray-600">Campaign: <a class="text-sky-600 hover:underline" href="{{ route('campaign.show', $c->slug) }}">{{ $c->title }}</a></div>
            @if ($a->payout?->amount)
                <div class="inline-block rounded-full bg-orange-50 px-2 py-1 text-xs text-orange-700 ring-1 ring-orange-200">Anggaran: Rp {{ number_format((float)$a->payout->amount, 2, ',', '.') }}</div>
            @endif
            @if ($a->cover_url)
                <img src="{{ $a->cover_url }}" alt="Cover" class="w-full rounded-md object-cover" />
            @endif
            @if ($a->body_md)
                <div class="prose max-w-none bg-white p-4 rounded-md shadow">
                    {!! $a->body_html !!}
                </div>
            @endif
        </article>

        <aside class="lg:col-span-1">
            <div class="sticky top-4 space-y-4">
                <div class="rounded-md bg-white p-5 shadow">
                    <h2 class="mb-1 text-lg font-semibold">Dukung Campaign Ini</h2>
                    <p class="text-sm text-gray-600">Klik tombol Donasi untuk melanjutkan ke halaman donasi.</p>
                </div>
            </div>
        </aside>
    </main>
    @php
        $shareUrlFab = route('campaign.show', $c->slug);
        $shareTextFab = $c->title;
        $encUrlFab = urlencode($shareUrlFab);
        $encTextFab = urlencode($shareTextFab);
    @endphp
    <div class="fixed inset-x-0 bottom-0 z-30 bg-white/95 backdrop-blur border-t border-gray-200">
        <div class="mx-auto max-w-7xl px-4 py-3 flex items-center gap-3">
            <button type="button" id="fab-share-trigger" class="inline-flex items-center justify-center rounded-md border border-orange-500 bg-white px-4 py-2.5 text-sm font-semibold text-orange-600 shadow-sm hover:bg-orange-50" aria-label="Bagikan">Bagikan</button>
            <a href="{{ route('campaign.donate.form', $c->slug) }}" class="flex-1 inline-flex items-center justify-center rounded-md bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-orange-600">Donasi</a>
            <div id="fab-share-popover" class="pointer-events-auto invisible absolute bottom-14 right-4 translate-y-2 opacity-0 transition-all duration-150 ease-out">
                <div class="flex items-center gap-3 rounded-xl bg-white p-2 shadow-lg ring-1 ring-gray-200">
                    <a href="https://wa.me/?text={{ $encTextFab }}%20{{ $encUrlFab }}" target="_blank" rel="noopener" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#25D366] text-white hover:opacity-90" aria-label="WhatsApp">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M20.52 3.48A11.93 11.93 0 0012.05 0C5.53.02.25 5.29.27 11.81a11.76 11.76 0 001.64 6.07L0 24l6.3-1.82a11.86 11.86 0 005.73 1.49h.01c6.52 0 11.8-5.27 11.82-11.79a11.8 11.8 0 00-3.34-8.4z"/></svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encUrlFab }}" target="_blank" rel="noopener" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#1877F2] text-white hover:opacity-90" aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M13.5 2.25a6.75 6.75 0 00-6.75 6.75v2.25H4.5a.75.75 0 00-.75 .75v3a.75.75 0 00.75 .75h2.25V21a.75.75 0 00.75 .75h3a.75.75 0 00.75 -.75v-5.25H14.6a.75.75 0 00.74 -.63l.38-3a.75.75 0 00-.74-.87H11.25V9a2.25 2.25 0 012.25-2.25H15a.75.75 0 00.75 -.75V3a.75.75 0 00-.75 -.75h-1.5z"/></svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?text={{ $encTextFab }}&url={{ $encUrlFab }}" target="_blank" rel="noopener" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-black text-white hover:opacity-90" aria-label="X">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M18.244 2H21l-6.52 7.455L22 22h-6.828l-5.34-7.027L3.6 22H1l7.035-8.04L2 2h6.914l4.83 6.42L18.244 2zm-2.392 18h1.662L7.225 4H5.47l10.382 16z"/></svg>
                    </a>
                    <a href="https://t.me/share/url?url={{ $encUrlFab }}&text={{ $encTextFab }}" target="_blank" rel="noopener" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#229ED9] text-white hover:opacity-90" aria-label="Telegram">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M9.04 15.36l-.37 5.2c.53 0 .76-.23 1.03-.5l2.48-2.38 5.14 3.77c.94 .52 1.6 .25 1.84 -.87l3.34 -15.71h.01c.3 -1.42 -.51 -1.98 -1.43 -1.63L1.4 9.93C.02 10.48 .04 11.3 1.14 11.64l5.2 1.62 12.06 -7.6c.57 -.35 1.1 -.16 .67 .22L9.04 15.36z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Floating share toggle (bottom bar)
        (function(){
            const trigger = document.getElementById('fab-share-trigger');
            const pop = document.getElementById('fab-share-popover');
            const openPop = () => { if (!pop) return; pop.classList.remove('opacity-0','invisible','translate-y-2'); };
            const closePop = () => { if (!pop) return; pop.classList.add('opacity-0','invisible','translate-y-2'); };
            trigger && trigger.addEventListener('click', (e) => { e.preventDefault(); if (pop.classList.contains('invisible')) openPop(); else closePop(); });
            document.addEventListener('click', (e) => { if (!pop || !trigger) return; if (pop.contains(e.target) || trigger.contains(e.target)) return; closePop(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });
        })();
    </script>
</body>
</html>
