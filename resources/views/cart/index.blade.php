@extends('layouts.storefront')

@section('seo_title', 'Keranjang — Nakama Project Hub')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-4 pb-24">
        <h1 class="text-lg font-semibold mb-3">Keranjang</h1>

        @if (session('status'))
            <div class="mb-4 rounded-xl bg-teal-50 border border-teal-200 px-4 py-3 text-sm text-teal-700 font-medium flex items-center gap-2">
                ✅ {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 font-medium flex items-center gap-2">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        @if (empty($items))
            <div class="rounded-2xl border border-gray-200 bg-white p-10 text-center">
                <div class="text-4xl mb-3">🛒</div>
                <div class="text-gray-500 text-sm">Keranjang masih kosong.</div>
                <a href="{{ route('event.index') }}"
                   class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">
                    Jelajahi Event
                </a>
            </div>
        @else
            <div class="space-y-3 mb-4">
                @foreach ($items as $it)
                    @php
                        $isReplay = ($it['item_type'] ?? 'ticket') === 'replay';
                        $removeRoute = $isReplay
                            ? route('cart.replay.remove', $it['slug'])
                            : route('cart.remove', $it['slug']);
                    @endphp
                    <div class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                        {{-- Cover --}}
                        <div class="relative shrink-0">
                            @if (!empty($it['cover_url']))
                                <img src="{{ $it['cover_url'] }}" alt="{{ $it['title'] }}"
                                     class="h-16 w-24 rounded-lg object-cover" />
                            @else
                                <div class="h-16 w-24 rounded-lg bg-gray-100 flex items-center justify-center text-2xl">
                                    {{ $isReplay ? '🎬' : '🎫' }}
                                </div>
                            @endif
                            {{-- Badge type --}}
                            @if ($isReplay)
                                <span class="absolute -top-1 -left-1 text-[10px] font-bold bg-teal-600 text-white px-1.5 py-0.5 rounded-full">
                                    REKAMAN
                                </span>
                            @else
                                <span class="absolute -top-1 -left-1 text-[10px] font-bold bg-sky-600 text-white px-1.5 py-0.5 rounded-full">
                                    TIKET
                                </span>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-gray-900">{{ $it['title'] }}</div>
                                    <div class="mt-0.5 text-xs text-gray-500">
                                        {{ ($it['unit_price'] ?? 0) > 0
                                            ? 'Rp ' . number_format($it['unit_price'], 0, ',', '.')
                                            : 'Gratis' }}
                                    </div>
                                </div>
                                {{-- Remove button --}}
                                <form method="post" action="{{ $removeRoute }}">
                                    @csrf
                                    <button type="submit" aria-label="Hapus" title="Hapus"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-red-500 hover:bg-red-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                          <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>

                            {{-- Qty stepper (only for ticket, not replay) --}}
                            @if (!$isReplay)
                                <div class="mt-2">
                                    <form method="post" action="{{ route('cart.update', $it['slug']) }}"
                                          class="inline-flex items-center gap-2" id="form-qty-{{ $loop->index }}">
                                        @csrf
                                        <input type="hidden" name="qty" id="qty-val-{{ $loop->index }}"
                                               value="{{ $it['qty'] ?? 1 }}" />
                                        <label class="text-xs text-gray-500 mr-1">Jumlah</label>
                                        {{-- Minus --}}
                                        <button type="button"
                                                onclick="stepQty({{ $loop->index }}, -1)"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 text-base font-bold">−</button>
                                        {{-- Angka --}}
                                        <span id="qty-display-{{ $loop->index }}"
                                              class="min-w-[1.75rem] text-center text-sm font-semibold text-gray-800">{{ $it['qty'] ?? 1 }}</span>
                                        {{-- Plus --}}
                                        <button type="button"
                                                onclick="stepQty({{ $loop->index }}, 1)"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 text-base font-bold">+</button>
                                        {{-- Ubah --}}
                                        <button type="submit"
                                                class="ml-1 rounded-lg bg-teal-600 px-3 py-1 text-xs font-semibold text-white hover:bg-teal-700">Ubah</button>
                                    </form>
                                </div>
                            @else
                                <div class="mt-2 text-xs text-gray-400 italic">Qty: 1 (rekaman tidak bisa duplikat)</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Total --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 flex items-center justify-between shadow-sm">
                <div class="text-sm text-gray-600">Total ({{ count($items) }} item)</div>
                <div class="text-lg font-bold text-gray-900">Rp {{ number_format($total, 0, ',', '.') }}</div>
            </div>

            <div class="mt-3 space-y-2">
                <a href="{{ route('order.checkout') }}"
                   class="inline-flex w-full items-center justify-center rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow hover:bg-teal-700">
                    Lanjut Checkout →
                </a>
                <form method="post" action="{{ route('cart.clear') }}">
                    @csrf
                    <button class="w-full text-sm text-gray-500 hover:text-red-500 hover:underline py-1"
                            type="submit">
                        Kosongkan Keranjang
                    </button>
                </form>
            </div>
        @endif
    </main>

    <script>
    function stepQty(index, delta) {
        var hidden  = document.getElementById('qty-val-' + index);
        var display = document.getElementById('qty-display-' + index);
        if (!hidden || !display) return;
        var current = parseInt(hidden.value) || 1;
        var next = Math.max(1, current + delta);
        hidden.value   = next;
        display.textContent = next;
    }
    </script>
@endsection
