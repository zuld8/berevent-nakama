@extends('layouts.storefront')

@section('title', 'Notifikasi')

@section('content')
  <main class="mx-auto max-w-7xl px-4 py-4">
    <div class="mb-4 flex items-center gap-3">
      <a href="{{ route('home') }}" aria-label="Kembali ke beranda" class="inline-flex items-center justify-center rounded-full p-2 ring-1 ring-gray-200 hover:bg-gray-50 text-gray-700">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
      </a>
      <h1 class="text-lg font-semi leading-6 text-gray-500">Notifikasi</h1>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600">
      Belum ada notifikasi. Fitur ini akan menampilkan update pembayaran, event, dan informasi akun Anda.
    </div>
  </main>
@endsection
