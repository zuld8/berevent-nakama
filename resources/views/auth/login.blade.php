<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Masuk — Nakama Project</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
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
            padding: 12px;
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
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4 py-8" style="font-family:'Inter',system-ui,sans-serif;">

    <div class="w-full max-w-sm">

        {{-- Logo --}}
        <div class="mb-6 flex justify-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2">
                <img src="/logo-icon.svg" alt="Nakama" class="h-9 w-9" onerror="this.style.display='none'">
                <span style="font-size:20px;font-weight:700;color:#0D9488;letter-spacing:-0.5px;">Nakama</span>
                <span style="font-size:20px;font-weight:300;color:#374151;">Project</span>
            </a>
        </div>

        {{-- Card --}}
        <div class="rounded-2xl bg-white px-6 py-7 shadow-sm border border-gray-100">

            <div class="mb-5">
                <h1 class="text-xl font-bold text-gray-900">Selamat Datang 👋</h1>
                <p class="mt-1 text-sm text-gray-500">Masuk ke akun Nakama Project kamu.</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 flex items-start gap-2 rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('login.attempt') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="email">Email</label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}"
                           placeholder="email@kamu.com"
                           required autofocus
                           class="input-field" />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="password">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               required
                               class="input-field" style="padding-right:40px;" />
                        <button type="button" class="eye-btn" onclick="togglePass()" aria-label="Lihat password">
                            <svg id="eyeOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eyeClosed" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 accent-teal-600" />
                        Ingat saya
                    </label>
                    <a href="#" class="text-sm font-medium text-teal-600 hover:text-teal-700">Lupa password?</a>
                </div>

                <button type="submit" class="btn-masuk">Masuk</button>
            </form>

            <p class="mt-5 text-center text-sm text-gray-500">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-medium text-teal-600 hover:text-teal-700">Daftar sekarang</a>
            </p>
        </div>

        <p class="mt-5 text-center text-xs text-gray-400">&copy; {{ date('Y') }} Nakama Project.</p>
    </div>

    <script>
        function togglePass() {
            const pwd = document.getElementById('password');
            const o = document.getElementById('eyeOpen');
            const c = document.getElementById('eyeClosed');
            if (pwd.type === 'password') { pwd.type='text'; o.classList.add('hidden'); c.classList.remove('hidden'); }
            else { pwd.type='password'; o.classList.remove('hidden'); c.classList.add('hidden'); }
        }
    </script>
</body>
</html>
