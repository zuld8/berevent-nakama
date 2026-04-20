@extends('layouts.storefront')

@section('title', 'Checkout')

@section('content')
    <main class="mx-auto max-w-2xl px-4 py-4">
        <h1 class="text-lg font-semibold mb-3">Checkout</h1>

        @if ($errors->any())
            <div class="mb-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('order.place') }}" id="checkoutForm">
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
            <div class="rounded-xl border border-gray-200 bg-white p-4 flex items-center justify-between mb-4">
                <div class="text-sm text-gray-600">Total Estimasi</div>
                <div class="text-lg font-semibold text-gray-900">Rp {{ number_format($total, 0, ',', '.') }}</div>
            </div>

            {{-- Payment Method Top-level --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="text-sm font-semibold text-gray-900 mb-3">Metode Pembayaran</div>
                <div class="space-y-2">

                    {{-- Manual Transfer --}}
                    <label id="lbl-manual"
                           class="flex items-center gap-3 rounded-xl border-2 border-gray-200 bg-white p-3 cursor-pointer transition-all hover:bg-gray-50">
                        <input type="radio" name="pay_method" value="manual" id="pm-manual"
                               class="shrink-0 accent-teal-600" onchange="switchMethod('manual')">
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">Transfer Manual</div>
                            <div class="text-xs text-gray-500 mt-0.5">Upload bukti transfer setelah pesanan dibuat.</div>
                        </div>
                        <svg class="h-5 w-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </label>

                    {{-- Duitku Automatic --}}
                    <label id="lbl-automatic"
                           class="flex items-center gap-3 rounded-xl border-2 border-teal-500 bg-teal-50 p-3 cursor-pointer transition-all">
                        <input type="radio" name="pay_method" value="automatic" id="pm-automatic"
                               class="shrink-0 accent-teal-600" checked onchange="switchMethod('automatic')">
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">Pembayaran Otomatis</div>
                            <div class="text-xs text-gray-500 mt-0.5">VA Bank, QRIS, e-Wallet via Duitku.</div>
                        </div>
                        <svg class="h-5 w-5 text-teal-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </label>
                </div>

                {{-- Duitku method list (shown when automatic selected) --}}
                <div id="duitku-methods" class="mt-4">
                    <div class="text-xs font-medium text-gray-500 mb-2">Pilih Metode Pembayaran</div>
                    <div class="space-y-2" id="duitku-method-list">
                        @foreach ($duitkuMethods as $m)
                            <label class="duitku-method-card flex items-center justify-between gap-3 rounded-xl border-2 border-gray-200 bg-white p-3 cursor-pointer transition-all hover:bg-gray-50"
                                   data-code="{{ $m['paymentMethod'] }}">
                                <span class="flex items-center gap-3">
                                    @if (!empty($m['paymentImage']))
                                        <img src="{{ $m['paymentImage'] }}" alt="{{ $m['paymentName'] }}"
                                             class="h-6 w-auto max-w-[60px] object-contain"
                                             onerror="this.style.display='none'">
                                    @endif
                                    <span class="text-sm font-medium text-gray-900">{{ $m['paymentName'] }}</span>
                                </span>
                                <input type="radio" name="payment_method" value="{{ $m['paymentMethod'] }}"
                                       class="shrink-0 accent-teal-600"
                                       onchange="highlightMethod(this.closest('label'))">
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <button type="submit" id="submitBtn"
                    class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white hover:bg-teal-700 active:scale-[0.98] transition-all shadow-sm">
                Buat Pesanan
            </button>
        </form>
    </main>

    <script>
        function switchMethod(val) {
            const lblManual    = document.getElementById('lbl-manual');
            const lblAutomatic = document.getElementById('lbl-automatic');
            const duitkuBox    = document.getElementById('duitku-methods');

            if (val === 'automatic') {
                lblAutomatic.classList.replace('border-gray-200', 'border-teal-500');
                lblAutomatic.classList.replace('bg-white', 'bg-teal-50');
                lblManual.classList.replace('border-teal-500', 'border-gray-200');
                lblManual.classList.replace('bg-teal-50', 'bg-white');
                duitkuBox.classList.remove('hidden');
            } else {
                lblManual.classList.replace('border-gray-200', 'border-teal-500');
                lblManual.classList.replace('bg-white', 'bg-teal-50');
                lblAutomatic.classList.replace('border-teal-500', 'border-gray-200');
                lblAutomatic.classList.replace('bg-teal-50', 'bg-white');
                duitkuBox.classList.add('hidden');
                // Clear method selection
                document.querySelectorAll('input[name="payment_method"]').forEach(el => el.checked = false);
                document.querySelectorAll('.duitku-method-card').forEach(el => {
                    el.classList.replace('border-teal-500', 'border-gray-200');
                    el.classList.replace('bg-teal-50', 'bg-white');
                });
            }
        }

        function highlightMethod(card) {
            document.querySelectorAll('.duitku-method-card').forEach(el => {
                el.classList.remove('border-teal-500', 'bg-teal-50');
                el.classList.add('border-gray-200', 'bg-white');
            });
            card.classList.remove('border-gray-200', 'bg-white');
            card.classList.add('border-teal-500', 'bg-teal-50');
        }

        // Make whole card clickable and trigger radio
        document.querySelectorAll('.duitku-method-card').forEach(function(card) {
            card.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) { radio.checked = true; highlightMethod(this); }
            });
        });

        // Validate: if automatic, must pick a method
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const isAuto = document.getElementById('pm-automatic').checked;
            if (isAuto) {
                const picked = document.querySelector('input[name="payment_method"]:checked');
                if (!picked) {
                    e.preventDefault();
                    alert('Pilih metode pembayaran terlebih dahulu.');
                }
            }
        });
    </script>
@endsection
