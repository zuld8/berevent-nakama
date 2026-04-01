@extends('layouts.storefront')

@section('title', 'Pembayaran Manual')

@section('content')
    <main class="mx-auto max-w-2xl px-4 py-6">
        <h1 class="text-lg font-semibold mb-2">Transfer Manual</h1>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-sm text-gray-600">Nomor Pesanan</div>
            <div class="text-base font-semibold text-gray-900">{{ $order->reference }}</div>
            <div class="mt-3 text-sm text-gray-700">
                Silakan transfer ke rekening berikut:
            </div>
            <ul class="mt-2 text-sm text-gray-800">
                <li><span class="text-gray-600">Bank</span>: {{ $bank['name'] ?? '-' }}</li>
                <li><span class="text-gray-600">Atas Nama</span>: {{ $bank['account_name'] ?? '-' }}</li>
                <li><span class="text-gray-600">No. Rekening</span>: {{ $bank['account_number'] ?? '-' }}</li>
            </ul>
            @if(!empty($bank['instructions']))
                <div class="mt-2 text-xs text-gray-600">{{ $bank['instructions'] }}</div>
            @endif
            @if($qrUrl)
                <div class="mt-3">
                    <img src="{{ $qrUrl }}" alt="QR" class="h-40 w-40 object-contain" />
                </div>
            @endif

            <form method="post" action="{{ route('order.manual.submit', $order->reference) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Upload Bukti</label>
                    <input type="file" name="proof" accept="image/*,application/pdf" class="mt-1 block w-full text-sm" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
                    <textarea name="note" rows="2" class="mt-1 w-full rounded-md border-gray-200 text-sm"></textarea>
                </div>
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white hover:bg-sky-700">Kirim Bukti</button>
            </form>
        </div>
    </main>
@endsection

