<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pembayaran Manual — {{ env('APP_NAME') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
</head>

<body class="bg-white text-gray-900 storefront-fixed">
    @include('partials.gtm-body', ['gtmId' => $gtmId])
    <main class="mx-auto max-w-2xl px-4 py-8">
        <div class="rounded-xl bg-white p-5 shadow">
            <h1 class="mb-1 text-xl font-semibold">Pembayaran Manual (Transfer)</h1>
            <p class="mb-4 text-sm text-gray-600">Referensi Donasi: <span
                      class="font-mono">{{ $donation->reference }}</span></p>

            <div class="mb-6 space-y-2">
                <div class="text-sm">Jumlah Donasi</div>
                <div class="text-2xl font-bold">Rp {{ number_format((float) $donation->amount, 0, ',', '.') }}</div>
            </div>

            <div class="mt-3 text-sm">
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <div class="mb-2 flex items-center gap-3">
                        <span class="font-semibold">Rincian Donasi</span>
                        <span class="flex-1 border-t border-gray-200"></span>
                    </div>
                    <div class="flex items-start justify-between py-1">
                        <div class="text-gray-700"><span class="font-medium">(1x)</span> Donasi</div>
                        <div id="sum-amount" class="font-medium">Rp {{ number_format((float) $donation->amount, 0, ',', '.') }}</div>
                    </div>
                    <div id="sum-fee-row" class="flex items-start justify-between py-1 hidden">
                        <div>
                            <div id="sum-fee-name" class="text-gray-700">Metode</div>
                            <div id="sum-fee-text" class="text-xs text-gray-500">Admin fee</div>
                        </div>
                        <div id="sum-fee-amount" class="font-medium">Rp 0</div>
                    </div>
                    <div class="my-2 border-t border-dashed border-gray-200"></div>
                    <div class="flex items-center justify-between">
                        <div class="font-semibold text-green-700">Total</div>
                        <div id="midtrans-total" class="font-bold text-green-700">Rp {{ number_format((float) $donation->amount, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <div class="mb-2 mt-4 text-sm font-semibold">Informasi Rekening</div>
                <div class="space-y-3">
                    <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">

                        <div class="flex flex-col">
                            <div class="text-xs text-gray-500">No. Rekening</div>
                            <div id="acc-number" class="font-mono text-2xl tracking-wide select-all">
                                {{ $bank['account_number'] ?? '-' }}
                            </div>
                        </div>

                        <button id="copy-acc-number" type="button"
                                class="self-start inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-md font-medium text-gray-700 hover:bg-gray-50 w-full sm:w-auto">
                            Salin
                        </button>
                    </div>

                    <div class="text-sm text-gray-700">
                        <div><span class="text-gray-500 text-xl">Bank</span> <span
                                  class="font-medium text-xl">{{ $bank['name'] ?? '-' }} -
                                {{ $bank['account_name'] ?? '-' }}</span></div>
                    </div>
                    <div id="copy-hint" class="hidden text-xs text-green-600">Nomor rekening disalin.</div>

                </div>
                @if (!empty($qrUrl))
                    <hr class="border-gray-200">
                    <div class="mt-4">
                        <div class="mb-1 text-sm text-gray-600">QRIS / QR Transfer:</div>
                        <img src="{{ $qrUrl }}" alt="QR" class="h-48 w-auto rounded-md border border-gray-200" />
                    </div>
                @endif
                @if (!empty($bank['instructions']))
                    <div class="mt-4">
                        <div class="mb-1 text-sm text-gray-600">Instruksi:</div>
                        <div class="whitespace-pre-wrap break-words rounded-md bg-gray-50 p-3 text-sm text-gray-800">
                            {!! nl2br(e($bank['instructions'])) !!}
                        </div>
                    </div>
                @endif
            </div>

            <div class="mb-6">
                <div class="mb-2 text-sm font-semibold">Upload Bukti Transfer</div>
                <form method="post"
                      action="{{ route('donation.manual.submit', ['reference' => $donation->reference]) }}"
                      enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="proof" accept="image/*,.pdf" required
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                    @error('proof')
                        <div class="text-xs text-red-600">{{ $message }}</div>
                    @enderror
                    <input type="text" name="note" placeholder="Catatan (opsional)"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                    <button type="submit"
                            class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Kirim
                        Bukti</button>
                </form>
                @if (!empty($payment->manual_proof_path))
                    <div class="mt-3 text-xs text-gray-600">Bukti terakhir sudah diunggah. Anda dapat mengunggah ulang jika
                        perlu.</div>
                @endif
            </div>

            <a href="{{ route('campaign.show', $donation->campaign->slug) }}" class="text-sky-600 hover:text-sky-700">←
                Kembali ke campaign</a>
        </div>
    </main>
</body>

</html>

<script>
    (function () {
        const btn = document.getElementById('copy-acc-number');
        const numEl = document.getElementById('acc-number');
        const hint = document.getElementById('copy-hint');
        if (btn && numEl) {
            btn.addEventListener('click', async () => {
                const text = (numEl.textContent || '').trim();
                if (!text) return;
                try {
                    await navigator.clipboard.writeText(text);
                    if (hint) {
                        hint.classList.remove('hidden');
                        setTimeout(() => hint.classList.add('hidden'), 1500);
                    }
                } catch (e) {
                    // fallback: select text
                    const range = document.createRange();
                    range.selectNodeContents(numEl);
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            });
        }
    })();
</script>
