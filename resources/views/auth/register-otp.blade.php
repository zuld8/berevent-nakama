@extends('layouts.storefront')

@section('title', 'Verifikasi OTP')

@section('content')
  <main class="mx-auto max-w-md px-4 py-8">
    <h1 class="text-lg font-semibold text-gray-900 mb-4">Verifikasi OTP</h1>
    <p class="text-sm text-gray-600 mb-3">Kami telah mengirim kode OTP ke WhatsApp: <span class="font-medium">{{ $phone }}</span>. Masukkan kode untuk aktivasi akun.</p>
    @if(session('status'))
      <p class="mb-3 text-sm text-green-700">{{ session('status') }}</p>
    @endif
    <form method="post" action="{{ route('register.otp.verify') }}" class="space-y-3">
      @csrf
      <input type="hidden" name="phone" value="{{ $phone }}" />
      <div>
        <label class="block text-sm font-medium text-gray-700">Kode OTP</label>
        <input type="text" name="code" maxlength="6" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500" required />
        @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
      </div>
      <button type="submit" class="mt-2 inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-4 py-3 text-sm font-medium text-white hover:bg-sky-700">Aktifkan Akun</button>
    </form>

    <form method="post" action="{{ route('register.otp.resend') }}" class="mt-3 flex items-center gap-3">
      @csrf
      <input type="hidden" name="phone" value="{{ $phone }}" />
      @php $cd = (int)($cooldown ?? 0); @endphp
      <button id="resend-btn" type="submit" data-cooldown="{{ $cd }}" class="text-sm text-sky-600 hover:underline {{ $cd > 0 ? 'pointer-events-none opacity-50' : '' }}" {{ $cd > 0 ? 'disabled' : '' }}>Kirim ulang OTP</button>
      <span id="resend-timer" class="text-xs text-gray-500 {{ $cd > 0 ? '' : 'hidden' }}">Tunggu {{ $cd }} detik</span>
      <a href="{{ route('register') }}" class="text-sm text-gray-600 hover:underline">Ganti nomor</a>
    </form>
  </main>
  <script>
    (function(){
      const btn = document.getElementById('resend-btn');
      const label = document.getElementById('resend-timer');
      if (!btn || !label) return;
      let cd = parseInt(btn.getAttribute('data-cooldown') || '0', 10);
      if (isNaN(cd) || cd <= 0) return;
      const tick = () => {
        cd -= 1;
        if (cd <= 0) {
          btn.removeAttribute('disabled');
          btn.classList.remove('pointer-events-none','opacity-50');
          label.classList.add('hidden');
          return;
        }
        label.textContent = 'Tunggu ' + cd + ' detik';
        setTimeout(tick, 1000);
      };
      setTimeout(tick, 1000);
    })();
  </script>
@endsection
