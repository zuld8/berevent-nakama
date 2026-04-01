@extends('layouts.storefront')

@section('title', 'Tiket Event')

@section('content')
    <main class="mx-auto max-w-2xl px-4 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-lg font-semibold">Tiket Anda</h1>
            <div class="text-sm text-gray-600">Order: {{ $order->reference }}</div>
          </div>
        </div>

        @if(($tickets ?? collect())->isEmpty())
            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 text-center text-gray-600">Belum ada tiket.</div>
        @else
            <div class="mt-4 space-y-4">
                @foreach($tickets as $t)
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                @php
                                    $ev = $t->event;
                                    $start = $ev?->start_date ? \Illuminate\Support\Carbon::parse($ev->start_date) : null;
                                    $end = $ev?->end_date ? \Illuminate\Support\Carbon::parse($ev->end_date) : null;
                                    try { if ($start) $start->locale('id'); if ($end) $end->locale('id'); } catch (\Throwable $e) {}
                                    $dateText = null;
                                    if ($start && $end) {
                                        $sameYear = $start->format('Y') === $end->format('Y');
                                        $sameMonth = $start->format('mY') === $end->format('mY');
                                        if ($sameMonth && $sameYear) {
                                            $dateText = $start->translatedFormat('d') . ' – ' . $end->translatedFormat('d M Y');
                                        } elseif ($sameYear) {
                                            $dateText = $start->translatedFormat('d M') . ' – ' . $end->translatedFormat('d M Y');
                                        } else {
                                            $dateText = $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y');
                                        }
                                    } elseif ($start) {
                                        $dateText = $start->translatedFormat('d M Y');
                                    }
                                    $modeText = match ($ev?->mode) {
                                        'online' => 'Online', 'offline' => 'Offline', 'both' => 'Online & Offline', default => 'Event'
                                    };
                                @endphp
                                <div class="text-sm font-semibold text-gray-900">{{ $ev?->title ?? 'Event' }}</div>
                                <div class="mt-0.5 text-xs text-gray-500">Kode: {{ $t->code }}</div>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $t->status === 'used' ? 'bg-gray-100 text-gray-700 ring-gray-200' : 'bg-green-50 text-green-700 ring-green-200' }}">{{ strtoupper($t->status) }}</span>
                        </div>
                        <div class="mt-3 flex items-center justify-center">
                            <img src="{{ route('ticket.qr', $t->code) }}" alt="QR {{ $t->code }}" class="h-48 w-48 object-contain rounded-md border border-gray-200 bg-white p-2" />
                        </div>

                        @if($ev)
                            <div class="mt-4 text-[13px] text-gray-700">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    <span>{{ $modeText }}</span>
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                    <span>{{ $dateText ?? 'Tanggal menyusul' }}</span>
                                </div>
                                @if($ev->description)
                                    <p class="mt-2 text-[13px] text-gray-600 line-clamp-3">{{ strip_tags($ev->description) }}</p>
                                @endif
                            </div>

                            @if(($ev->materials ?? collect())->isNotEmpty())
                                <div class="mt-4">
                                    <h4 class="text-sm font-semibold text-gray-900">Sesi / Materi</h4>
                                    <div class="relative mt-2">
                                        <div class="absolute left-4 top-0 bottom-0 w-px bg-gray-200"></div>
                                        <div class="space-y-4">
                                            @foreach($ev->materials as $mat)
                                                <div class="relative pl-10">
                                                    <span class="absolute left-4 -translate-x-1/2 top-1.5 h-2.5 w-2.5 rounded-full bg-sky-500 ring-2 ring-white"></span>
                                                    <div class="text-[13px] font-medium text-sky-700">{{ $mat->title }}</div>
                                                    <div class="mt-1 flex items-center gap-2 text-[12px] text-gray-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                                        </svg>
                                                        <span>{{ optional($mat->date_at)->format('d M Y') }}</span>
                                                        @if($mat->mentor)
                                                            <span>•</span>
                                                            <span>{{ $mat->mentor?->name }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </main>
    
@endsection
