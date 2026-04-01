<div class="space-y-3">
    <div class="text-sm text-gray-600">Identitas: <span class="font-mono">{{ $identity }}</span></div>
    @if ($rows->isEmpty())
        <div class="text-sm text-gray-600">Belum ada riwayat donasi.</div>
    @else
        <div class="overflow-hidden rounded-md border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-white">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-700">Campaign</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-700">Transaksi</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-700">Total</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-700">Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach ($rows as $r)
                        <tr>
                            <td class="px-3 py-2">
                                @if ($r->campaign)
                                    <a href="{{ route('campaign.show', $r->campaign->slug) }}" target="_blank" class="text-sky-700 hover:underline">{{ $r->campaign->title }}</a>
                                @else
                                    <span class="text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">{{ (int) $r->trx }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format((float) $r->total, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">{{ $r->last_paid_at ? \Illuminate\Support\Carbon::parse($r->last_paid_at)->format('d M Y') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

