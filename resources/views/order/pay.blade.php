@extends('layouts.storefront')

@section('title', 'Pembayaran Pesanan')

@section('content')
    <main class="mx-auto max-w-2xl px-4 py-6">
        <h1 class="text-lg font-semibold mb-2">Pembayaran</h1>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-sm text-gray-600">Nomor Pesanan</div>
            <div class="text-base font-semibold text-gray-900">{{ $order->reference }}</div>
            <div class="mt-2 text-sm text-gray-600">Total</div>
            <div class="text-lg font-semibold text-gray-900">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</div>
            <p class="mt-4 text-sm text-gray-500">Anda akan diarahkan ke halaman pembayaran Duitku.</p>
            <a href="{{ $paymentUrl }}"
               class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-teal-600 px-4 py-3 text-sm font-medium text-white hover:bg-teal-700">
                Bayar Sekarang
            </a>
        </div>
    </main>
    <script>
        // Auto-redirect to Duitku payment page
        window.location.href = '{{ $paymentUrl }}';
    </script>
@endsection
