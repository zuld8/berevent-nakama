<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donatur — {{ env('APP_NAME') }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @php
        $analytics = $org?->meta_json['analytics'] ?? [];
        $gtmId = $analytics['gtm_id'] ?? null;
    @endphp
    @include('partials.gtm-head', ['gtmId' => $gtmId])
</head>
<body class="bg-white text-gray-900 storefront-fixed">
    @include('partials.gtm-body', ['gtmId' => $gtmId])
    <header class="bg-white border-b border-gray-200">
        <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="text-sky-600 hover:text-sky-700">← Beranda</a>
            <h1 class="text-lg font-semibold">Daftar Donatur</h1>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        <div class="rounded-md bg-white p-4 shadow">
            <form method="get" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="sm:col-span-3">
                    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama, email, atau nomor HP" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400" />
                </div>
                <div class="sm:col-span-1 flex items-center gap-2">
                    <select name="sort" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400">
                        <option value="amount" {{ $sort==='amount'?'selected':'' }}>Terbesar</option>
                        <option value="recent" {{ $sort==='recent'?'selected':'' }}>Terbaru</option>
                        <option value="count" {{ $sort==='count'?'selected':'' }}>Terbanyak</option>
                    </select>
                    <button class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">Filter</button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Donatur</th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Transaksi</th>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($donors as $d)
                            <tr>
                                <td class="px-4 py-2 text-sm">
                                    <div class="font-medium text-gray-900">{{ $d->donor_name ?: ($d->donor_email ?: ($d->donor_phone ?: '—')) }}</div>
                                    <div class="text-xs text-gray-600">{{ $d->donor_email ?: '—' }} @if($d->donor_phone) · {{ $d->donor_phone }} @endif</div>
                                </td>
                                <td class="px-4 py-2 text-right text-sm">Rp {{ number_format((float)$d->total_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right text-sm">{{ (int) $d->donation_count }}</td>
                                <td class="px-4 py-2 text-sm">{{ optional($d->last_paid_at)->format('d M Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-600">Belum ada data donatur.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $donors->links() }}</div>
        </div>
    </main>
</body>
</html>
