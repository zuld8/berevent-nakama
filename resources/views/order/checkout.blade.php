@extends('layouts.storefront')

@section('title', 'Checkout')

@section('content')
    <main class="mx-auto max-w-2xl px-4 py-4">
        <h1 class="text-lg font-semibold mb-3">Checkout</h1>

        <form method="post" action="{{ route('order.place') }}">
            @csrf
            <div class="space-y-3 mb-4">
                @foreach ($items as $it)
                    <div class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-3">
                        @if (!empty($it['cover_url']))
                            <img src="{{ $it['cover_url'] }}" alt="{{ $it['title'] }}" class="h-16 w-24 rounded-lg object-cover" />
                        @else
                            <div class="h-16 w-24 rounded-lg bg-gray-100"></div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-gray-900">{{ $it['title'] }}</div>
                                    <div class="mt-0.5 text-xs text-gray-500">Qty: {{ $it['qty'] ?? 1 }}</div>
                                </div>
                                @if (($it['price_type'] ?? 'fixed') !== 'fixed')
                                    <div class="text-right">
                                        <label class="block text-[12px] text-gray-500">Nominal</label>
                                        <input type="number" name="prices[{{ $it['slug'] }}]" min="0" step="1000" value="{{ (int)($it['unit_price'] ?? 0) }}" class="w-28 rounded-md border-gray-200 text-sm text-right px-2 py-1" />
                                    </div>
                                @else
                                    <div class="text-right">
                                        <span class="block text-[12px] text-gray-500">Harga</span>
                                        <span class="block text-sm font-semibold text-gray-900">Rp {{ number_format((int)($it['unit_price'] ?? 0), 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 flex items-center justify-between">
                <div class="text-sm text-gray-600">Total Estimasi</div>
                <div class="text-lg font-semibold text-gray-900">Rp {{ number_format($total, 0, ',', '.') }}</div>
            </div>

            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4">
                <div class="text-sm font-semibold text-gray-900 mb-2">Metode Pembayaran</div>
                <div class="space-y-2 text-sm text-gray-700">
                    <label class="flex items-start gap-2">
                        <input type="radio" name="pay_method" value="manual" class="mt-1" checked>
                        <div>
                            <div class="font-medium">Transfer Manual</div>
                            <div class="text-xs text-gray-500">Upload bukti transfer setelah pesanan dibuat.</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <input type="radio" name="pay_method" value="automatic" class="mt-1">
                        <div>
                            <div class="font-medium">Pembayaran Otomatis</div>
                            <div class="text-xs text-gray-500">Bayar via Midtrans (VA/QRIS/Kartu).</div>
                        </div>
                    </label>
                </div>
                <div id="auto-methods" class="mt-3 hidden">
                    <div class="text-xs text-gray-500 mb-2">Pilih Metode Pembayaran</div>
                    <div class="space-y-2">
                        @foreach(($methods ?? []) as $m)
                            <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-3 hover:bg-gray-50">
                                <span class="flex items-center gap-3">
                                    @if (!empty($m['logo']))
                                        <img src="{{ $m['logo'] }}" alt="{{ $m['name'] }}" class="h-5 w-auto" />
                                    @endif
                                    <span class="text-sm font-medium text-gray-900">{{ $m['name'] }}</span>
                                    {{-- <span class="text-xs text-gray-500">{{ $catalog->feeText($m) }}</span> --}}
                                </span>
                                <input type="radio" name="payment_method" value="{{ $m['id'] }}" class="shrink-0">
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <button type="submit" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white hover:bg-sky-700">Buat Pesanan</button>
        </form>
    </main>
    <script>
        (function(){
            const manual = document.querySelector('input[name="pay_method"][value="manual"]');
            const auto = document.querySelector('input[name="pay_method"][value="automatic"]');
            const box = document.getElementById('auto-methods');
            const sync = () => { if (auto && auto.checked) { box && box.classList.remove('hidden'); } else { box && box.classList.add('hidden'); } };
            manual && manual.addEventListener('change', sync);
            auto && auto.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
