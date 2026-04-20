@extends('layouts.storefront')

@section('title', 'Checkout')

@section('content')
    <main class="mx-auto max-w-2xl px-4 py-4">
        <h1 class="text-lg font-semibold mb-3">Checkout</h1>

        <form method="post" action="{{ route('order.place') }}">
            @csrf

            {{-- Order Items --}}
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
                                        <input type="number" name="prices[{{ $it['slug'] }}]"
                                               min="0" step="1000"
                                               value="{{ (int)($it['unit_price'] ?? 0) }}"
                                               class="w-28 rounded-md border-gray-200 text-sm text-right px-2 py-1" />
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

            {{-- Total --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 flex items-center justify-between">
                <div class="text-sm text-gray-600">Total Estimasi</div>
                <div class="text-lg font-semibold text-gray-900">Rp {{ number_format($total, 0, ',', '.') }}</div>
            </div>

            {{-- Payment Method --}}
            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4">
                <div class="text-sm font-semibold text-gray-900 mb-3">Metode Pembayaran</div>
                <div class="space-y-2 text-sm text-gray-700">

                    {{-- Manual Transfer --}}
                    <label id="label-manual"
                           class="flex items-start gap-3 rounded-xl border-2 border-gray-200 bg-white p-3 cursor-pointer transition-colors hover:bg-gray-50"
                           onclick="selectMethod('manual')">
                        <input type="radio" name="pay_method" value="manual" id="pm-manual" class="mt-0.5 shrink-0 accent-teal-600">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900">Transfer Manual</div>
                            <div class="text-xs text-gray-500 mt-0.5">Upload bukti transfer setelah pesanan dibuat.</div>
                        </div>
                        <svg class="mt-0.5 h-5 w-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </label>

                    {{-- Duitku (Automatic) --}}
                    <label id="label-automatic"
                           class="flex items-start gap-3 rounded-xl border-2 border-teal-500 bg-teal-50 p-3 cursor-pointer transition-colors"
                           onclick="selectMethod('automatic')">
                        <input type="radio" name="pay_method" value="automatic" id="pm-automatic" class="mt-0.5 shrink-0 accent-teal-600" checked>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900">Pembayaran Otomatis</div>
                            <div class="text-xs text-gray-500 mt-0.5">Via Duitku — VA Bank, QRIS, e-Wallet, dan lainnya.</div>
                            {{-- Duitku payment logos --}}
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <img src="https://images.duitku.com/hotlink-ok/BCA.PNG"  alt="BCA"     class="h-4 w-auto opacity-80" onerror="this.style.display='none'">
                                <img src="https://images.duitku.com/hotlink-ok/BNI.PNG"  alt="BNI"     class="h-4 w-auto opacity-80" onerror="this.style.display='none'">
                                <img src="https://images.duitku.com/hotlink-ok/BRI.PNG"  alt="BRI"     class="h-4 w-auto opacity-80" onerror="this.style.display='none'">
                                <img src="https://images.duitku.com/hotlink-ok/QRIS.PNG" alt="QRIS"    class="h-4 w-auto opacity-80" onerror="this.style.display='none'">
                                <img src="https://images.duitku.com/hotlink-ok/MD.PNG"   alt="Mandiri" class="h-4 w-auto opacity-80" onerror="this.style.display='none'">
                                <span class="text-[10px] text-gray-400">& lainnya</span>
                            </div>
                        </div>
                        <svg class="mt-0.5 h-5 w-5 text-teal-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </label>
                </div>
            </div>

            {{-- Submit --}}
            <button type="submit"
                    class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white hover:bg-teal-700 active:scale-[0.98] transition-all shadow-sm">
                Buat Pesanan
            </button>
        </form>
    </main>

    <script>
        function selectMethod(val) {
            const labels = { manual: 'label-manual', automatic: 'label-automatic' };
            const inputs = { manual: 'pm-manual', automatic: 'pm-automatic' };

            // Reset all
            Object.values(labels).forEach(id => {
                const el = document.getElementById(id);
                el.classList.remove('border-teal-500', 'bg-teal-50');
                el.classList.add('border-gray-200', 'bg-white');
            });

            // Activate selected
            const active = document.getElementById(labels[val]);
            active.classList.remove('border-gray-200', 'bg-white');
            active.classList.add('border-teal-500', 'bg-teal-50');

            // Check the radio
            document.getElementById(inputs[val]).checked = true;
        }

        // Init
        document.querySelectorAll('input[name="pay_method"]').forEach(function(el) {
            el.addEventListener('change', function() { selectMethod(this.value); });
        });
    </script>
@endsection
