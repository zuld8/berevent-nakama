@extends('layouts.storefront')

@section('title', 'Daftar')

@section('content')
  <main class="mx-auto max-w-md px-4 py-8">
    <h1 class="text-lg font-semibold text-gray-900 mb-4">Daftar Akun</h1>
    <form method="post" action="{{ route('register.submit') }}" class="space-y-3">
      @csrf
      <div>
        <label class="block text-sm font-medium text-gray-700">Nama</label>
        <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" required />
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" required />
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Nomor WhatsApp</label>
        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="08xxxx / 62xxxxx" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" required />
        <p class="mt-1 text-xs text-gray-500">Nomor akan divalidasi dan dikirim OTP via WhatsApp.</p>
        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Password</label>
        <input type="password" name="password" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" required />
        @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" required />
      </div>
      <button type="submit" class="mt-2 inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white hover:bg-sky-700">Daftar</button>
    </form>
  </main>
@endsection

