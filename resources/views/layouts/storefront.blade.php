<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    {{-- ── Title ── --}}
    <title>@yield('seo_title', config('app.name', 'Nakama Project Hub'))</title>

    {{-- ── Primary SEO ── --}}
    <meta name="description" content="@yield('seo_description', 'Platform event, workshop, dan komunitas terpercaya. Daftar tiket, tonton rekaman, dan download materi eksklusif di Nakama Project Hub.')" />
    <meta name="robots" content="@yield('seo_robots', 'index, follow')" />
    <meta name="author" content="{{ config('app.name', 'Nakama Project Hub') }}" />
    <meta http-equiv="Content-Language" content="id-ID" />
    @if(View::hasSection('seo_canonical'))
        <link rel="canonical" href="@yield('seo_canonical')" />
    @endif

    {{-- ── Open Graph ── --}}
    <meta property="og:site_name" content="{{ config('app.name', 'Nakama Project Hub') }}" />
    <meta property="og:locale" content="id_ID" />
    <meta property="og:type" content="@yield('og_type', 'website')" />
    <meta property="og:title" content="@yield('og_title', config('app.name', 'Nakama Project Hub'))" />
    <meta property="og:description" content="@yield('og_description', 'Platform event, workshop, dan komunitas terpercaya.')" />
    <meta property="og:url" content="@yield('seo_canonical', url()->current())" />
    @if(View::hasSection('og_image') && trim($__env->yieldContent('og_image')) !== '')
        <meta property="og:image" content="@yield('og_image')" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
    @endif

    {{-- ── Twitter Card ── --}}
    @if(View::hasSection('og_image') && trim($__env->yieldContent('og_image')) !== '')
        <meta name="twitter:card" content="summary_large_image" />
    @else
        <meta name="twitter:card" content="summary" />
    @endif
    <meta name="twitter:title" content="@yield('og_title', config('app.name', 'Nakama Project Hub'))" />
    <meta name="twitter:description" content="@yield('og_description', 'Platform event, workshop, dan komunitas terpercaya.')" />
    @if(View::hasSection('og_image') && trim($__env->yieldContent('og_image')) !== '')
        <meta name="twitter:image" content="@yield('og_image')" />
    @endif

    {{-- ── PWA / Theme ── --}}
    <meta name="theme-color" content="#0ea5e9" />
    <link rel="icon" href="/favicon.ico" />

    {{-- ── Structured Data (JSON-LD) ── --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "{{ config('app.name', 'Nakama Project Hub') }}",
        "url": "{{ config('app.url', url('/')) }}",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "{{ url('/') }}?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    @stack('json_ld')

    @vite(['resources/css/app.css','resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Poppins', sans-serif; }
    </style>
    @stack('head')
  </head>
  <body class="bg-gray-100 text-gray-900 storefront-fixed">
    <div class="sf-canvas mx-auto bg-white min-h-screen">
        @yield('content')
    </div>
    @include('partials.bottom-nav')
  </body>
</html>
