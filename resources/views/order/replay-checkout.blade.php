@extends('layouts.storefront')

@section('title', 'Checkout Rekaman — ' . $event->title)

@section('content')
<main class="mx-auto max-w-2xl px-4 py-6 pb-24">

    {{-- Back --}}
    <a href="{{ route('event.show', $event->slug) }}"
       class="mb-4 inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
             stroke="currentColor" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Kembali ke Event
    </a>

    <h1 class="text-lg font-semibold text-gray-900 mb-4">Beli Rekaman Event</h1>

    {{-- Item Summary --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 mb-4 flex items-center gap-4">
        @if($event->cover_url)
            <img src="{{ $event->cover_url }}" alt="{{ $event->title }}"
                 class="h-16 w-24 rounded-lg object-cover shrink-0"/>
        @else
            <div class="h-16 w-24 rounded-lg bg-gray-100 shrink-0"></div>
        @endif
        <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-gray-900 truncate">🎬 Rekaman: {{ $event->title }}</div>
            <div class="text-xs text-gray-500 mt-0.5">Akses selamanya setelah pembayaran dikonfirmasi</div>
        </div>
        <div class="text-right shrink-0">
            <div class="text-xs text-gray-500">Harga</div>
            <div class="text-base font-bold text-gray-900">
                @if($replayPrice > 0)
                    Rp {{ number_format($replayPrice, 0, ',', '.') }}
                @else
                    Gratis
                @endif
            </div>
        </div>
    </div>

    {{-- Payment Method Form --}}
    <form method="POST" action="{{ route('event.replay.buy', $event->slug) }}">
        @csrf

        <div class="rounded-xl border border-gray-200 bg-white p-4 mb-4">
            <div class="text-sm font-semibold text-gray-900 mb-3">Metode Pembayaran</div>
            <div class="space-y-2 text-sm text-gray-700">

                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="radio" name="pay_method" value="manual" class="mt-1" checked>
                    <div>
                        <div class="font-medium">Transfer Manual</div>
                        <div class="text-xs text-gray-500">Upload bukti transfer setelah order dibuat.</div>
                    </div>
                </label>

                @if(!empty($methods))
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="radio" name="pay_method" value="automatic" class="mt-1">
                    <div>
                        <div class="font-medium">Pembayaran Otomatis</div>
                        <div class="text-xs text-gray-500">Bayar via Midtrans (VA / QRIS / Kartu).</div>
                    </div>
                </label>

                <div id="auto-methods" class="hidden mt-2 space-y-2 pl-6">
                    <div class="text-xs text-gray-500 mb-1">Pilih Metode:</div>
                    @foreach($methods as $m)
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-3 hover:bg-gray-50 cursor-pointer">
                            <span class="flex items-center gap-3">
                                @if(!empty($m['logo']))
                                    <img src="{{ $m['logo'] }}" alt="{{ $m['name'] }}" class="h-5 w-auto"/>
                                @endif
                                <span class="text-sm font-medium text-gray-900">{{ $m['name'] }}</span>
                            </span>
                            <input type="radio" name="payment_method" value="{{ $m['id'] }}" class="shrink-0">
                        </label>
                    @endforeach
                </div>
                @endif

            </div>
        </div>

        <button type="submit"
                class="inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-4 py-3.5 text-sm font-semibold text-white shadow hover:bg-sky-700 transition">
            🎬 Konfirmasi & Bayar
        </button>
    </form>
</main>

<script>
(function(){
    const auto = document.querySelector('input[name="pay_method"][value="automatic"]');
    const manual = document.querySelector('input[name="pay_method"][value="manual"]');
    const box = document.getElementById('auto-methods');
    const sync = () => box && (auto?.checked ? box.classList.remove('hidden') : box.classList.add('hidden'));
    auto?.addEventListener('change', sync);
    manual?.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
