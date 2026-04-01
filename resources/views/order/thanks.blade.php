@extends('layouts.storefront')

@section('title', 'Terima Kasih')

@section('content')
    <main class="mx-auto max-w-2xl px-4 min-h-[70vh] flex flex-col items-center justify-center text-center">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Terima kasih!</h1>
            <p class="mt-2 text-sm text-gray-600">Pesanan {{ $order->reference }} tercatat. Status: {{ strtoupper($order->status) }}.</p>
            <div class="mt-6 flex items-center justify-center gap-2">
                <a class="inline-flex items-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700" href="{{ route('order.tickets', $order->reference) }}">Lihat Tiket</a>
                <a class="inline-flex items-center rounded-xl bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200" href="{{ route('home') }}">Beranda</a>
            </div>
        </div>
    </main>
@endsection
