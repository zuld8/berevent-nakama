<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Masuk — Nakama Project</title>
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
        .btn-masuk {
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
        .btn-masuk:hover { background: #0b8073; }
        .btn-masuk:active { transform: scale(0.98); }

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
    <div class="flex flex-1 flex-col items-center justify-center px-6 py-10 bg-white lg:max-w-lg xl:max-w-xl">

        {{-- Logo --}}
        <div class="mb-8 w-full max-w-sm">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2">
                <img src="/logo-icon.svg" alt="Nakama" class="h-9 w-9" onerror="this.style.display='none'">
                <span style="font-size:20px;font-weight:700;color:#0D9488;letter-spacing:-0.5px;">Nakama</span>
                <span style="font-size:20px;font-weight:300;color:#374151;">Project</span>
            </a>
        </div>

        {{-- Card --}}
        <div class="w-full max-w-sm">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Selamat Datang 👋</h1>
                <p class="mt-1 text-sm text-gray-500">Masuk ke akun Nakama Project kamu.</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 flex items-start gap-2 rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('login.attempt') }}" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="email">Email</label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}"
                           placeholder="email@kamu.com"
                           required autofocus
                           class="input-field" />
                </div>

                {{-- Password --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="password">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               required
                               class="input-field" style="padding-right:40px;" />
                        <button type="button" class="eye-btn" onclick="togglePass()" id="eyeBtn" aria-label="Lihat password">
                            <svg id="eyeOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eyeClosed" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember + Forgot --}}
                <div class="flex items-center justify-between">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 accent-teal-600" />
                        Ingat saya
                    </label>
                    <a href="#" class="text-sm font-medium text-teal-600 hover:text-teal-700">Lupa password?</a>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-masuk">Masuk</button>
            </form>

            <p class="mt-5 text-center text-sm text-gray-500">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-medium text-teal-600 hover:text-teal-700">Daftar sekarang</a>
            </p>
        </div>

        <p class="mt-10 text-xs text-gray-400">&copy; {{ date('Y') }} Nakama Project. All rights reserved.</p>
    </div>

    {{-- RIGHT — Brand Panel (desktop only) --}}
    <div class="hidden login-gradient lg:flex flex-1 flex-col items-center justify-center px-10 py-10 relative overflow-hidden">
        {{-- Decorative circles --}}
        <div class="floating-dot" style="width:300px;height:300px;top:-80px;right:-80px;"></div>
        <div class="floating-dot" style="width:200px;height:200px;bottom:-60px;left:-60px;"></div>
        <div class="floating-dot" style="width:120px;height:120px;top:50%;left:20px;"></div>

        <div class="relative z-10 max-w-sm text-white text-center">
            {{-- Large logo icon --}}
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-sm">
                <img src="/logo-icon.svg" alt="Nakama" class="h-12 w-12" onerror="
                    this.style.display='none';
                    this.parentElement.innerHTML='<span style=\'font-size:36px;font-weight:900;color:white;\'>N</span>';
                ">
            </div>
            <h2 class="text-3xl font-bold leading-tight mb-3">
                Temukan Event,<br>Beli Tiket, Donasi —<br>Semua di Sini
            </h2>
            <p class="text-white/75 text-sm leading-relaxed mb-8">
                Nakama Project adalah tempat kamu menemukan event seru, mendukung kampanye kebaikan, dan terhubung dengan komunitas.
            </p>

            {{-- Feature list — end user perspective --}}
            <div class="space-y-3 text-left">
                @foreach([
                    ['🎟️', 'Beli tiket event dengan mudah & aman'],
                    ['💝', 'Donasi & infaq ke kampanye pilihan kamu'],
                    ['📱', 'Tiket digital langsung di HP, tanpa repot'],
                    ['🤝', 'Bergabung dengan komunitas Nakama'],
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
    function togglePass() {
        const pwd = document.getElementById('password');
        const open = document.getElementById('eyeOpen');
        const closed = document.getElementById('eyeClosed');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            open.classList.add('hidden');
            closed.classList.remove('hidden');
        } else {
            pwd.type = 'password';
            open.classList.remove('hidden');
            closed.classList.add('hidden');
        }
    }
</script>
</body>
</html>
