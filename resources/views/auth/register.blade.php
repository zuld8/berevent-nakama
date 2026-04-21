<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daftar — Nakama Project</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .login-gradient {
            background: linear-gradient(135deg, #0D9488 0%, #0a7a6e 40%, #065f5a 100%);
        }
        .floating-dot {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
        }
        .input-field {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            background: #fff;
        }
        .input-field:focus {
            border-color: #0D9488;
            box-shadow: 0 0 0 3px rgba(13,148,136,.12);
        }
        .input-error { border-color: #f87171; }
        .btn-daftar {
            background: #0D9488;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 11px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: background .2s, transform .1s;
        }
        .btn-daftar:hover { background: #0b8073; }
        .btn-daftar:active { transform: scale(0.98); }
        .eye-btn {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #9ca3af; padding: 2px;
        }
        .eye-btn:hover { color: #0D9488; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" style="font-family:'Inter',system-ui,sans-serif;">

<div class="flex min-h-screen">

    {{-- LEFT — Form --}}
    <div class="flex flex-1 flex-col items-center justify-center px-6 py-10 bg-white lg:max-w-lg xl:max-w-xl overflow-y-auto">

        {{-- Logo --}}
        <div class="mb-6 w-full max-w-sm">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2">
                <img src="/logo-icon.svg" alt="Nakama" class="h-9 w-9" onerror="this.style.display='none'">
                <span style="font-size:20px;font-weight:700;color:#0D9488;letter-spacing:-0.5px;">Nakama</span>
                <span style="font-size:20px;font-weight:300;color:#374151;">Project</span>
            </a>
        </div>

        <div class="w-full max-w-sm">
            <div class="mb-5">
                <h1 class="text-2xl font-bold text-gray-900">Buat Akun Baru 🎉</h1>
                <p class="mt-1 text-sm text-gray-500">Daftar gratis dan mulai jelajahi event & kampanye.</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 flex items-start gap-2 rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('register.submit') }}" class="space-y-4">
                @csrf

                {{-- Nama --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name"
                           value="{{ old('name') }}"
                           placeholder="Nama kamu"
                           required autofocus
                           class="input-field {{ $errors->has('name') ? 'input-error' : '' }}" />
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="email">Email</label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}"
                           placeholder="email@kamu.com"
                           required
                           class="input-field {{ $errors->has('email') ? 'input-error' : '' }}" />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- WhatsApp --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="phone">
                        Nomor WhatsApp
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">
                            📱
                        </span>
                        <input type="text" id="phone" name="phone"
                               value="{{ old('phone') }}"
                               placeholder="08xxx atau 62xxx"
                               required
                               class="input-field {{ $errors->has('phone') ? 'input-error' : '' }}"
                               style="padding-left: 36px;" />
                    </div>
                    <p class="mt-1 text-xs text-gray-400">OTP verifikasi akan dikirim via WhatsApp.</p>
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="password">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password"
                               placeholder="Min. 8 karakter"
                               required
                               class="input-field {{ $errors->has('password') ? 'input-error' : '' }}"
                               style="padding-right:40px;" />
                        <button type="button" class="eye-btn" onclick="togglePass('password','eye1o','eye1c')" aria-label="Lihat password">
                            <svg id="eye1o" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye1c" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi Password --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="password_confirmation">Konfirmasi Password</label>
                    <div class="relative">
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               placeholder="Ulangi password"
                               required
                               class="input-field"
                               style="padding-right:40px;" />
                        <button type="button" class="eye-btn" onclick="togglePass('password_confirmation','eye2o','eye2c')" aria-label="Lihat konfirmasi password">
                            <svg id="eye2o" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye2c" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-daftar">Daftar Sekarang</button>
            </form>

            <p class="mt-5 text-center text-sm text-gray-500">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-medium text-teal-600 hover:text-teal-700">Masuk di sini</a>
            </p>
        </div>

        <p class="mt-8 text-xs text-gray-400">&copy; {{ date('Y') }} Nakama Project. All rights reserved.</p>
    </div>

    {{-- RIGHT — Brand Panel (desktop only) --}}
    <div class="hidden login-gradient lg:flex flex-1 flex-col items-center justify-center px-10 py-10 relative overflow-hidden">
        <div class="floating-dot" style="width:300px;height:300px;top:-80px;right:-80px;"></div>
        <div class="floating-dot" style="width:200px;height:200px;bottom:-60px;left:-60px;"></div>
        <div class="floating-dot" style="width:120px;height:120px;top:50%;left:20px;"></div>

        <div class="relative z-10 max-w-sm text-white text-center">
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-sm">
                <img src="/logo-icon.svg" alt="Nakama" class="h-12 w-12" onerror="
                    this.style.display='none';
                    this.parentElement.innerHTML='<span style=\'font-size:36px;font-weight:900;color:white;\'>N</span>';
                ">
            </div>
            <h2 class="text-3xl font-bold leading-tight mb-3">
                Gabung Nakama,<br>Gratis Selamanya 🎉
            </h2>
            <p class="text-white/75 text-sm leading-relaxed mb-8">
                Ribuan orang sudah bergabung. Temukan event seru, dukung kampanye kebaikan, dan dapatkan tiket digital dengan mudah.
            </p>
            <div class="space-y-3 text-left">
                @foreach([
                    ['✅', 'Daftar gratis, tanpa biaya apapun'],
                    ['🎟️', 'Akses semua event & beli tiket langsung'],
                    ['💝', 'Donasi & infaq ke kampanye pilihan kamu'],
                    ['📱', 'OTP verifikasi via WhatsApp — cepat & aman'],
                ] as [$icon, $text])
                    <div class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 backdrop-blur-sm">
                        <span class="text-xl">{{ $icon }}</span>
                        <span class="text-sm font-medium text-white">{{ $text }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

<script>
    function togglePass(fieldId, openId, closeId) {
        const f = document.getElementById(fieldId);
        const o = document.getElementById(openId);
        const c = document.getElementById(closeId);
        if (f.type === 'password') {
            f.type = 'text'; o.classList.add('hidden'); c.classList.remove('hidden');
        } else {
            f.type = 'password'; o.classList.remove('hidden'); c.classList.add('hidden');
        }
    }
</script>
</body>
</html>
