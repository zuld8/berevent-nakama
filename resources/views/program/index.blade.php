<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Program — {{ env('APP_NAME') }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @php
        $analytics = $org?->meta_json['analytics'] ?? [];
        $gtmId = $analytics['gtm_id'] ?? null;
    @endphp
    @include('partials.gtm-head', ['gtmId' => $gtmId])
</head>
<body class="bg-white text-gray-900 storefront-fixed">
    @include('partials.gtm-body', ['gtmId' => $gtmId])
    <header class="bg-white border-b border-gray-200">
        <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="text-sky-600 hover:text-sky-700">← Beranda</a>
            <nav class="hidden md:flex items-center gap-6 text-sm">
                @php
                    $tabBase = 'text-gray-700 hover:text-sky-700';
                    $tabActive = 'text-sky-700 font-medium';
                @endphp
                @include('partials.menu-links', ['tabBase' => $tabBase, 'tabActive' => $tabActive])
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold">Program</h1>
            <form method="get" class="sm:hidden w-full max-w-xs">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari Program" class="w-full rounded-md border border-gray-300 bg-white pl-10 pr-3 py-2 text-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400" style="padding-left: 15%" />
                </div>
            </form>
        </div>

        @if ($categories->count())
            <div class="mt-2 mb-5">
                <div class="rounded-md bg-white p-3 shadow">
                    <div class="flex flex-wrap items-center gap-2">
                        @php $isAll = empty($activeCategory); @endphp
                        <a href="{{ route('program.index', ['q' => $q]) }}"
                           class="inline-flex items-center rounded-md px-3 py-2 text-sm ring-1 {{ $isAll ? 'ring-sky-400 text-sky-700 bg-sky-50' : 'ring-gray-200 text-gray-700 hover:ring-sky-300 hover:text-sky-700' }}">
                            Semua
                        </a>
                        @foreach ($categories as $cat)
                            @php $active = $activeCategory === $cat->slug; @endphp
                            <a href="{{ route('program.index', ['category' => $cat->slug, 'q' => $q]) }}"
                               class="inline-flex items-center rounded-md px-3 py-2 text-sm ring-1 {{ $active ? 'ring-sky-400 text-sky-700 bg-sky-50' : 'ring-gray-200 text-gray-700 hover:ring-sky-300 hover:text-sky-700' }}">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div id="program-grid" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @include('partials.campaign-cards', ['campaigns' => $campaigns])
        </div>

        @php $hasMore = $campaigns->hasMorePages(); $nextPage = $campaigns->currentPage() + 1; @endphp
        <div class="mt-8 text-center">
            <button id="load-more"
                    data-next-page="{{ $nextPage }}"
                    class="inline-flex items-center justify-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:opacity-50 {{ $hasMore ? '' : 'hidden' }}">
                Muat lebih banyak
            </button>
        </div>
    </main>

    <script>
        (function(){
            const btn = document.getElementById('load-more');
            const grid = document.getElementById('program-grid');
            if (!btn || !grid) return;
            const baseUrl = '{{ route('program.chunk') }}';
            const paramsBase = new URLSearchParams({
                q: '{{ $q }}',
                @if ($activeCategory) category: '{{ $activeCategory }}', @endif
            });
            btn.addEventListener('click', async () => {
                const next = btn.getAttribute('data-next-page');
                if (!next) return;
                btn.disabled = true;
                try {
                    const url = baseUrl + '?' + paramsBase.toString() + '&page=' + next;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('Gagal memuat');
                    const data = await res.json();
                    const tmp = document.createElement('div');
                    tmp.innerHTML = data.html;
                    // Append each article inside to grid
                    Array.from(tmp.children).forEach(child => grid.appendChild(child));
                    if (data.hasMore) {
                        btn.setAttribute('data-next-page', data.nextPage);
                        btn.disabled = false;
                    } else {
                        btn.classList.add('hidden');
                    }
                } catch (e) {
                    console.error(e);
                    btn.disabled = false;
                }
            });
        })();
    </script>
    @include('partials.bottom-nav')
</body>
</html>
