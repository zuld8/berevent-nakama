<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pilih Metode Bayar — {{ env('APP_NAME') }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        .method-card { transition: border-color .15s, background .15s; }
        .method-card.active { border-color: #0D9488; background: #F0FDFA; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 storefront-fixed">

    <main class="mx-auto flex min-h-screen max-w-lg flex-col px-4 py-6">
        <h1 class="text-lg font-bold mb-1">Pilih Metode Pembayaran</h1>
        <p class="text-xs text-gray-500 mb-4">
            Donasi ke <strong>{{ $donation->campaign?->title ?? 'Campaign' }}</strong> •
            <span class="font-semibold">Rp {{ number_format($donation->amount, 0, ',', '.') }}</span>
        </p>

        @if ($errors->any())
            <div class="mb-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('donation.choose.method', ['reference' => $donation->reference]) }}" id="methodForm">
            @csrf
            <div class="space-y-2" id="method-list">
                @foreach ($duitkuMethods as $m)
                    <label class="method-card flex items-center justify-between gap-3
                                  rounded-xl border-2 border-gray-200 bg-white p-3 cursor-pointer"
                           data-code="{{ $m['paymentMethod'] }}">
                        <span class="flex items-center gap-3">
                            @if (!empty($m['paymentImage']))
                                <img src="{{ $m['paymentImage'] }}" alt="{{ $m['paymentName'] }}"
                                     class="h-6 w-auto max-w-[64px] object-contain"
                                     onerror="this.style.display='none'">
                            @endif
                            <span class="text-sm font-medium text-gray-900">{{ $m['paymentName'] }}</span>
                        </span>
                        <input type="radio" name="payment_method" value="{{ $m['paymentMethod'] }}"
                               class="shrink-0 accent-teal-600">
                    </label>
                @endforeach
            </div>

            <button type="submit" id="submitBtn"
                    class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white hover:bg-teal-700 active:scale-[0.98] transition-all shadow-sm">
                Lanjutkan Pembayaran
            </button>
        </form>
    </main>

    <script>
        document.querySelectorAll('.method-card').forEach(function(card) {
            card.addEventListener('click', function() {
                document.querySelectorAll('.method-card').forEach(c => c.classList.remove('active'));
                const radio = this.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
                this.classList.add('active');
            });
        });

        document.getElementById('methodForm').addEventListener('submit', function(e) {
            const picked = document.querySelector('input[name="payment_method"]:checked');
            if (!picked) { e.preventDefault(); alert('Pilih metode pembayaran terlebih dahulu.'); }
        });
    </script>
</body>
</html>
