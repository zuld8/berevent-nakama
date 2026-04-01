<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Berita — {{ env('APP_NAME') }}</title>
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
        <div class="mb-6 flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold">Berita</h1>
            <form method="get" class="w-full max-w-xs">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari berita" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400" />
            </form>
        </div>
        @if ($news->count() === 0)
            <p class="text-gray-600">Belum ada berita.</p>
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($news as $n)
                    <a href="{{ route('news.show', $n->slug) }}" class="group block overflow-hidden rounded-md bg-white shadow hover:ring-1 hover:ring-sky-200">
                        <article>
                            @if ($n->cover_url)
                                <img src="{{ $n->cover_url }}" alt="{{ $n->title }}" class="aspect-video w-full object-cover" />
                            @endif
                            <div class="p-4">
                                <h2 class="line-clamp-2 text-base font-semibold text-gray-900 group-hover:text-sky-700">{{ $n->title }}</h2>
                                <div class="mt-1 text-xs text-gray-500">{{ optional($n->published_at)->format('d M Y') }} @if($n->author?->name) · {{ $n->author->name }} @endif</div>
                                @if ($n->excerpt)
                                    <p class="mt-2 line-clamp-3 text-sm text-gray-700">{{ $n->excerpt }}</p>
                                @endif
                            </div>
                        </article>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $news->links() }}</div>
        @endif
    </main>
</body>
</html>
