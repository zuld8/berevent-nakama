@extends('layouts.storefront')

@section('title', 'Kalender — ' . ($event->title ?? 'Event'))

@section('content')
  <main class="mx-auto max-w-3xl px-4 py-6">
    <a href="{{ route('calendar.index') }}" class="text-sm text-sky-600 hover:underline">← Kembali</a>

    <div class="mt-2 rounded-xl border border-gray-200 bg-white p-4">
      <div class="flex items-start justify-between gap-3">
        <h1 class="text-lg font-semibold text-gray-900">{{ $event->title }}</h1>
        <a href="{{ route('calendar.print', $event->slug) }}" target="_blank" class="inline-flex items-center rounded-md bg-sky-600 px-3 py-2 text-xs font-medium text-white hover:bg-sky-700">Download PDF</a>
      </div>
      @php
        $start = $event->start_date ? \Illuminate\Support\Carbon::parse($event->start_date) : null;
        $end = $event->end_date ? \Illuminate\Support\Carbon::parse($event->end_date) : null;
        try { if ($start) $start->locale('id'); if ($end) $end->locale('id'); } catch (\Throwable $e) {}
        $dateText = null;
        if ($start && $end) {
          $dateText = $start->format('Ymd') === $end->format('Ymd') ? $start->translatedFormat('d M Y') : ($start->translatedFormat('d M Y') . ' — ' . $end->translatedFormat('d M Y'));
        } elseif ($start) { $dateText = $start->translatedFormat('d M Y'); }
      @endphp
      <div class="mt-1 text-sm text-gray-600">{{ $dateText ?? 'Tanggal menyusul' }}</div>

      @if(($tickets ?? collect())->isEmpty())
        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">Tidak ada tiket untuk event ini.</div>
      @else
        <h2 class="mt-4 text-sm font-semibold text-gray-900">Tiket Anda</h2>
        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
          @foreach($tickets as $t)
            <div class="rounded-lg border border-gray-200 bg-white p-3">
              <div class="text-xs text-gray-500">Kode</div>
              <div class="text-sm font-semibold">{{ $t->code }}</div>
              <div class="mt-2 flex items-center justify-center">
                <img src="{{ route('ticket.qr', $t->code) }}" alt="QR {{ $t->code }}" class="h-40 w-40 object-contain" />
              </div>
            </div>
          @endforeach
        </div>
      @endif

      @if(($event->materials ?? collect())->isNotEmpty())
        <h2 class="mt-6 text-sm font-semibold text-gray-900">Jadwal Sesi</h2>
        <div class="relative mt-2">
          <div class="absolute left-4 top-0 bottom-0 w-px bg-gray-200"></div>
          <div class="space-y-4">
            @foreach($event->materials as $mat)
              <div class="relative pl-10">
                <span class="absolute left-4 -translate-x-1/2 top-1.5 h-2.5 w-2.5 rounded-full bg-sky-500 ring-2 ring-white"></span>
                <div class="text-[13px] font-medium text-sky-700">{{ $mat->title }}</div>
                <div class="mt-1 text-[12px] text-gray-600">{{ optional($mat->date_at)->format('d M Y') }} @if($mat->mentor) — {{ $mat->mentor?->name }} @endif</div>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </main>
@endsection
