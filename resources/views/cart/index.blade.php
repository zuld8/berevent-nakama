@extends('layouts.storefront')

@section('title', 'Keranjang')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-4">
        <h1 class="text-lg font-semibold mb-3">Keranjang</h1>

        @if (empty($items))
            <div class="rounded-md border border-gray-200 bg-white p-6 text-center text-gray-600">Keranjang kosong.</div>
        @else
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
                                    <div class="mt-0.5 text-xs text-gray-500">{{ ($it['unit_price'] ?? 0) > 0 ? 'Rp ' . number_format($it['unit_price'], 0, ',', '.') : 'Donasi/Gratis' }}</div>
                                </div>
                                <form method="post" action="{{ route('cart.remove', $it['slug']) }}">
                                    @csrf
                                    <button type="submit" aria-label="Hapus" title="Hapus"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-red-600 hover:bg-red-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            <div class="mt-2">
                                <form method="post" action="{{ route('cart.update', $it['slug']) }}" class="inline-flex items-center gap-2">
                                    @csrf
                                    <label class="text-xs text-gray-600">Jumlah</label>
                                    <input type="number" name="qty" min="1" value="{{ $it['qty'] ?? 1 }}" class="w-16 rounded-md border-gray-200 text-sm" />
                                    <button class="rounded-md bg-sky-600 px-2 py-1 text-xs font-medium text-white hover:bg-sky-700" type="submit">Ubah</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 flex items-center justify-between">
                <div class="text-sm text-gray-600">Total</div>
                <div class="text-lg font-semibold text-gray-900">Rp {{ number_format($total, 0, ',', '.') }}</div>
            </div>

            <div class="mt-3">
                <div class="flex items-center justify-between">
                    <form method="post" action="{{ route('cart.clear') }}">
                        @csrf
                        <button class="text-sm text-gray-600 hover:underline" type="submit">Kosongkan Keranjang</button>
                    </form>
                </div>
                <a href="{{ route('order.checkout') }}" class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white hover:bg-sky-700">Checkout</a>
            </div>
        @endif
    </main>
@endsection
