<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @php
        $metaTitle = $n->meta_title ?: $n->title;
        $metaDesc = $n->meta_description ?: ($n->excerpt ?: '');
        $metaImage = $n->meta_image_url ?: $n->cover_url;
        $metaUrl = route('news.show', $n->slug);
    @endphp
    <title>{{ $metaTitle }} — {{ env('APP_NAME') }}</title>
    @if ($metaDesc)
        <meta name="description" content="{{ $metaDesc }}">
    @endif
    <link rel="canonical" href="{{ $metaUrl }}">
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
        <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between gap-4">
            <a href="{{ route('news.index') }}" class="text-sky-600 hover:text-sky-700">← Kembali ke Berita</a>
            <nav class="hidden md:flex items-center gap-6 text-sm">
                @php
                    $tabBase = 'text-gray-700 hover:text-sky-700';
                    $tabActive = 'text-sky-700 font-medium';
                @endphp
                @include('partials.menu-links', ['tabBase' => $tabBase, 'tabActive' => $tabActive])
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <article class="lg:col-span-2 space-y-3">
            <h1 class="text-2xl font-bold leading-tight">{{ $n->title }}</h1>
            <div class="text-sm text-gray-500">{{ optional($n->published_at)->format('d M Y') }} @if($n->author?->name) · {{ $n->author->name }} @endif</div>
            @if ($n->cover_url)
                <img src="{{ $n->cover_url }}" alt="Cover" class="w-full rounded-md object-cover" />
            @endif
            @if ($n->body_md)
                <div class="prose max-w-none bg-white p-4 rounded-md shadow">
                    {!! $n->body_html !!}
                </div>
            @endif
        </article>

        <aside class="lg:col-span-1">
            <div class="sticky top-4 space-y-4">
                @if (($latest ?? collect())->isNotEmpty())
                    <div class="rounded-md bg-white p-5 shadow">
                        <h2 class="mb-3 text-lg font-semibold">Berita Lainnya</h2>
                        <div class="space-y-3">
                            @foreach ($latest as $i)
                                <a href="{{ route('news.show', $i->slug) }}" class="block text-sm text-gray-800 hover:text-sky-700">
                                    <div class="text-xs text-gray-500">{{ optional($i->published_at)->format('d M Y') }}</div>
                                    <div class="line-clamp-2">{{ $i->title }}</div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </aside>
    </main>
</body>
</html>
