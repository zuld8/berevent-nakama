@extends('layouts.storefront')

@section('title', 'Kalender Saya')

@section('content')
  <main class="mx-auto max-w-7xl px-4 py-6">
    <section class="mb-3">
      @include('partials.header-user', [
        'user' => auth()->user(),
        'location' => 'Kalender Saya',
        'notif' => 0,
        'query' => '',
        'org' => null,
        'searchRouteName' => 'calendar.index',
        'showFilterButton' => true,
      ])
    </section>
    @php $list = $items ?? collect(); @endphp

    {{-- Modal Filter Tanggal (mirip halaman event) --}}
    <div id="event-filter-modal" class="fixed inset-0 z-50 hidden">
      <div id="event-filter-backdrop" class="absolute inset-0 bg-black/50"></div>
      <div class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-white p-4 shadow-2xl">
        <div class="mx-auto max-w-2xl">
          <div class="mb-3 flex items-center justify-between">
            <h3 class="text-base font-semibold">Filter Tanggal</h3>
            <button type="button" id="event-filter-close" class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-gray-100" aria-label="Tutup">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
          </div>
          <form method="get" action="{{ route('calendar.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Mulai Tanggal</label>
              <input type="date" name="start" value="{{ $start ?? '' }}" class="w-full rounded-lg border-gray-200 text-sm" />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Sampai Tanggal</label>
              <input type="date" name="end" value="{{ $end ?? '' }}" class="w-full rounded-lg border-gray-200 text-sm" />
            </div>
            <div class="flex items-end gap-2">
              <button type="submit" class="inline-flex items-center rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white shadow hover:bg-sky-700">Terapkan</button>
              <a href="{{ route('calendar.index') }}" class="text-sm text-gray-600 hover:underline">Reset</a>
            </div>
          </form>
        </div>
      </div>
    </div>
    @if ($list->isEmpty())
      <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-gray-600">Belum ada event yang diikuti.</div>
    @else
      <div class="mb-2 text-sm font-semi">Event Yang Kamu Ikuti</div>
      <div class="grid grid-cols-2 gap-3">
        @foreach ($list as $row)
          @php $e = $row['event']; $count = (int)($row['ticketCount'] ?? 0); $codes = $row['tickets'] ?? []; @endphp
          @include('partials.event-card-calendar', ['event' => $e, 'ticketCount' => $count, 'tickets' => $codes])
        @endforeach
      </div>
    @endif
  </main>
  <script>
    (function(){
      const openBtn = document.getElementById('event-filter-open');
      const closeBtn = document.getElementById('event-filter-close');
      const modal = document.getElementById('event-filter-modal');
      const backdrop = document.getElementById('event-filter-backdrop');
      const open = () => { if (!modal) return; modal.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); };
      const close = () => { if (!modal) return; modal.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); };
      openBtn && openBtn.addEventListener('click', (e)=>{ e.preventDefault(); open(); });
      closeBtn && closeBtn.addEventListener('click', (e)=>{ e.preventDefault(); close(); });
      backdrop && backdrop.addEventListener('click', close);
      document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') close(); });
    })();
  </script>
@endsection
