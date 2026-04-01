@extends('layouts.storefront')

@section('title', 'Edit Profil')

@section('content')
  <main class="mx-auto max-w-7xl px-4 py-4">
    <div class="mb-4 flex items-center gap-3">
      <a href="{{ route('profile.index') }}" aria-label="Kembali ke profil" class="inline-flex items-center justify-center rounded-full p-2 ring-1 ring-gray-200 hover:bg-gray-50 text-gray-700">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
      </a>
      <h1 class="text-lg font-semi leading-6 text-gray-500">Edit Profil</h1>
    </div>

    <div class="bg-white">
      @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
      @endif

      <form method="post" action="{{ route('profile.update') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @csrf
        <div>
          <label class="block text-sm font-medium text-gray-700">Nama</label>
          <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Kontak Telepon</label>
          <input type="text" name="phone" value="{{ old('phone', optional($user->contact)->phone) }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Kontak Email</label>
          <input type="email" name="contact_email" value="{{ old('contact_email', optional($user->contact)->email) }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" />
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Alamat</label>
          <textarea name="address" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">{{ old('address', optional($user->contact)->address) }}</textarea>
        </div>

        <div class="sm:col-span-2 flex items-center gap-2">
          <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </main>
@endsection

