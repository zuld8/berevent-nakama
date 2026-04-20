<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Memproses Pembayaran — {{ env('APP_NAME') }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @php
        $orgAnalytics = $donation->campaign?->organization?->meta_json['analytics'] ?? [];
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
        .spinner { width: 24px; height: 24px; border: 3px solid #e5e7eb; border-top-color: #0D9488; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-white text-gray-900 storefront-fixed">
    @include('partials.gtm-body', ['gtmId' => $gtmId])
    <main class="mx-auto flex min-h-screen max-w-2xl items-center justify-center px-4">
        <div class="w-full rounded-xl bg-white p-8 text-center shadow">
            <div class="mx-auto mb-4 spinner"></div>
            <h1 class="mb-2 text-2xl font-bold">Mengarahkan ke halaman pembayaran…</h1>
            <p class="text-gray-600">Harap tunggu, Anda akan diarahkan ke halaman pembayaran Duitku.</p>
            <p class="mt-2 text-sm text-gray-500">Referensi: <span class="font-mono">{{ $donation->reference }}</span></p>
            <p class="mt-4 text-xs text-gray-400">Jika tidak diarahkan otomatis, <a href="{{ $paymentUrl }}" class="text-teal-600 underline">klik di sini</a>.</p>
        </div>
    </main>
    <script>
        // Auto-redirect to Duitku payment page
        window.location.href = '{{ $paymentUrl }}';
    </script>
</body>
</html>
