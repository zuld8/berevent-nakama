@extends('layouts.storefront')

@section('title', 'Masuk')

@section('bottom_nav')
    {{-- Hide bottom nav on login --}}
@endsection

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 min-h-[100vh] flex items-center justify-center">
        <div class="w-full max-w-sm">
            <div class="mb-6 text-center">
                <h1 class="text-xl font-semibold text-gray-900">Selamat datang di {{ env('APP_NAME') }}</h1>
                <p class="mt-1 text-sm text-gray-600">Silakan masuk untuk melanjutkan</p>
            </div>

            <div class="bg-white">
                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('login') }}" class="grid grid-cols-1 gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" required
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" />
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500" />
                            Ingat saya
                        </label>
                        <div class="text-sm">
                            <a href="#" class="text-sky-600 hover:text-sky-700">Lupa password?</a>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="w-full rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">Masuk</button>
                    </div>
                </form>
                <div class="mt-3 text-center text-sm text-gray-600">
                    Belum punya akun? <a href="{{ route('register') }}" class="text-sky-600 hover:text-sky-700">Daftar</a>
                </div>
            </div>
        </div>
    </main>
@endsection
