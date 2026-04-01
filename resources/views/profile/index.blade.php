@extends('layouts.storefront')

@section('title', 'Profile')

@section('content')
    <main class="mx-auto max-w-7xl">
        {{-- Profile photo uploader (top section) --}}
        <section class="">
            <div class="bg-white p-4">
                <div class="flex items-start gap-4">
                    <img id="profile-photo-preview"
                         src="{{ auth()->user()?->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name ?? 'U') . '&background=E5E7EB&color=111827' }}"
                         alt="Profile Photo" class="h-20 w-20 rounded-full object-cover ring-2 ring-white shadow" />
                    <form id="photo-form" action="{{ route('profile.photo') }}" method="post" enctype="multipart/form-data"
                          class="flex-1">
                        @csrf
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label for="photo-input"
                                       class="block text-sm font-medium text-gray-700">{{ auth()->user()->name ?? '—' }}</label>
                                <input id="photo-input" type="file" name="photo" accept="image/*" class="mt-1 block text-sm"
                                       required />
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="photo-upload-btn"
                                        class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50">Upload</button>
                                <a href="{{ route('profile.edit') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-200 hover:bg-gray-200">Edit Profil</a>
                            </div>
                        </div>
                        <input type="hidden" name="_use_client_processed" value="1" />
                    </form>
                </div>
                <p id="photo-error" class="mt-2 hidden text-sm text-red-600"></p>
                <p id="photo-status" class="mt-2 hidden text-sm text-green-700"></p>
            </div>
        </section>
        <script>
            (function () {
                const input = document.getElementById('photo-input');
                const preview = document.getElementById('profile-photo-preview');
                const btn = document.getElementById('photo-upload-btn');
                const form = document.getElementById('photo-form');
                const err = document.getElementById('photo-error');
                const ok = document.getElementById('photo-status');

                let selectedFile = null;

                input.addEventListener('change', () => {
                    err.classList.add('hidden'); ok.classList.add('hidden');
                    const f = input.files && input.files[0];
                    if (!f) return;
                    selectedFile = f;
                    const url = URL.createObjectURL(f);
                    preview.src = url;
                });

                async function cropAndCompress(file, maxBytes = 100 * 1024) {
                    const bitmap = await createImageBitmap(file).catch(() => null);
                    if (!bitmap) throw new Error('Tidak bisa membaca gambar ini.');
                    const size = Math.min(bitmap.width, bitmap.height);
                    const sx = Math.floor((bitmap.width - size) / 2);
                    const sy = Math.floor((bitmap.height - size) / 2);
                    let dim = Math.min(size, 640);
                    let quality = 0.9;
                    for (let attempt = 0; attempt < 8; attempt++) {
                        const canvas = document.createElement('canvas');
                        canvas.width = dim; canvas.height = dim;
                        const ctx = canvas.getContext('2d');
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(bitmap, sx, sy, size, size, 0, 0, dim, dim);
                        let blob = await new Promise(res => canvas.toBlob(res, 'image/jpeg', quality));
                        if (blob && blob.size <= maxBytes) return blob;
                        if (quality > 0.5) {
                            quality -= 0.1;
                        } else {
                            dim = Math.floor(dim * 0.85);
                            if (dim < 160) break;
                        }
                    }
                    const finalCanvas = document.createElement('canvas');
                    finalCanvas.width = Math.max(128, Math.floor(dim));
                    finalCanvas.height = Math.max(128, Math.floor(dim));
                    const fctx = finalCanvas.getContext('2d');
                    fctx.imageSmoothingQuality = 'high';
                    fctx.drawImage(bitmap, sx, sy, size, size, 0, 0, finalCanvas.width, finalCanvas.height);
                    const finalBlob = await new Promise(res => finalCanvas.toBlob(res, 'image/jpeg', 0.5));
                    if (!finalBlob) throw new Error('Gagal memproses gambar.');
                    if (finalBlob.size > maxBytes) throw new Error('Gambar terlalu besar setelah kompresi.');
                    return finalBlob;
                }

                btn.addEventListener('click', async () => {
                    err.classList.add('hidden'); ok.classList.add('hidden');
                    if (!selectedFile) { err.textContent = 'Silakan pilih file foto.'; err.classList.remove('hidden'); return; }
                    btn.disabled = true; btn.textContent = 'Mengunggah...';
                    try {
                        const blob = await cropAndCompress(selectedFile);
                        const token = form.querySelector('input[name=_token]').value;
                        const fd = new FormData();
                        fd.append('_token', token);
                        fd.append('_use_client_processed', '1');
                        fd.append('photo', new File([blob], 'profile.jpg', { type: 'image/jpeg' }));
                        const res = await fetch(form.action, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('Gagal mengunggah foto.');
                        const data = await res.json().catch(() => ({}));
                        ok.textContent = (data && data.message) ? data.message : 'Foto profil berhasil diperbarui.';
                        ok.classList.remove('hidden');
                        if (data && data.url) {
                            preview.src = data.url;
                        } else {
                            const ts = Date.now();
                            preview.src = preview.src.split('?')[0] + '?t=' + ts;
                        }
                    } catch (e) {
                        err.textContent = e.message || 'Terjadi kesalahan saat memproses.';
                        err.classList.remove('hidden');
                    } finally {
                        btn.disabled = false; btn.textContent = 'Upload';
                    }
                });
            })();
        </script>

        {{-- User information --}}
        {{-- Tabs: Informasi Pribadi / Transaksi --}}
        <section>
            <div class="bg-white">
                <div class="">
                    <nav class="grid grid-cols-2 ring-1 ring-gray-200 overflow-hidden" aria-label="Tabs">
                        <button id="tab-info" type="button"
                                class="tab-btn active w-full bg-sky-50 px-3 py-3 text-sm font-medium text-sky-700 ring-inset ring-1 ring-sky-200 focus:outline-none"
                                aria-controls="panel-info" aria-selected="true">
                            Informasi Pribadi
                        </button>

                        <button id="tab-trx" type="button"
                                class="tab-btn w-full bg-gray-50 px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none"
                                aria-controls="panel-trx" aria-selected="false">
                            Transaksi
                        </button>
                    </nav>

                </div>
                <div class="p-4">
                    <div id="panel-info" role="tabpanel">
                        <div class="mb-6">
                            <h2 class="mb-3 text-base font-semibold text-gray-900">Informasi Akun</h2>
                            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs tracking-wide text-gray-500">Nama</dt>
                                    <dd class="text-sm text-gray-900">{{ auth()->user()->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs tracking-wide text-gray-500">Email</dt>
                                    <dd class="text-sm text-gray-900">{{ auth()->user()->email ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs tracking-wide text-gray-500">Tipe</dt>
                                    <dd class="text-sm text-gray-900">{{ auth()->user()->type ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs tracking-wide text-gray-500">Kontak Telepon</dt>
                                    <dd class="text-sm text-gray-900">{{ optional(auth()->user()->contact)->phone ?? '—' }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs tracking-wide text-gray-500">Alamat</dt>
                                    <dd class="text-sm text-gray-900">{{ optional(auth()->user()->contact)->address ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <h2 class="mb-3 text-base font-semibold text-gray-900">Ubah Password</h2>
                            <form method="post" action="{{ route('profile.password') }}"
                                  class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @csrf
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Password Saat Ini</label>
                                    <input type="password" name="current_password"
                                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                                           autocomplete="current-password" />
                                    @error('current_password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Password Baru</label>
                                    <input type="password" name="password" required
                                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                                           autocomplete="new-password" />
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                                    <input type="password" name="password_confirmation" required
                                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                                           autocomplete="new-password" />
                                </div>
                                <div class="sm:col-span-2">
                                    <button type="submit"
                                            class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">Simpan
                                        Password</button>
                                </div>
                            </form>
                            @if ($errors->any() && !$errors->has('photo'))
                                <p class="mt-2 text-sm text-red-600">Periksa kembali input Anda.</p>
                            @endif
                            @if (session('status') && !str_contains(session('status'), 'Foto'))
                                <p class="mt-2 text-sm text-green-700">{{ session('status') }}</p>
                            @endif
                        </div>
                         <section class="flex flex-col items-left justify-left min-h-[150px] space-y-2 mt-4">
                            <div class="text-left">
                                <p class="text-gray-600 text-sm">
                                    Anda saat ini sudah login. Klik tombol di bawah ini untuk keluar dari akun.
                                </p>
                            </div>

                            <form method="post" action="{{ route('logout') }}" class="mb-6">
                                @csrf
                                <button type="submit"
                                        class="rounded-lg bg-red px-4 py-2 text-sm font-medium text-red-700 ring-1 ring-red-200 hover:bg-red-200 transition">
                                    Logout
                                </button>
                            </form>
                        </section>

                    </div>

                    <div id="panel-trx" role="tabpanel" class="hidden">
                        <h2 class="mb-3 text-base font-semibold text-gray-900">Transaksi</h2>
                        @php $orderList = $orders ?? collect(); @endphp
                        @if ($orderList->isEmpty())
                            <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600">Belum ada transaksi.</div>
                        @else
                            <div class="space-y-3">
                                @foreach ($orderList as $o)
                                    @php
                                        $status = strtoupper($o->status);
                                        $created = optional($o->created_at)->format('d M Y H:i');
                                        $total = 'Rp ' . number_format((float) $o->total_amount, 0, ',', '.');
                                        $ptype = data_get($o->meta_json, 'payment_type', 'manual');
                                        $payRoute = $ptype === 'automatic' ? route('order.pay', $o->reference) : route('order.manual', $o->reference);
                                        if ($o->status === 'paid') {
                                            $ctaText = 'Lihat Tiket';
                                            $ctaHref = route('order.tickets', $o->reference);
                                        } else {
                                            $ctaText = $ptype === 'automatic' ? 'Bayar' : 'Upload Bukti';
                                            $ctaHref = $payRoute;
                                        }
                                    @endphp
                                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">{{ $o->reference }}</div>
                                                <div class="mt-0.5 text-xs text-gray-500">{{ $created }}</div>
                                            </div>
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $o->status === 'paid' ? 'bg-green-50 text-green-700 ring-green-200' : ($o->status === 'failed' ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-amber-50 text-amber-700 ring-amber-200') }}">{{ $status }}</span>
                                        </div>
                                        <div class="mt-2 text-sm text-gray-700">Total: <span class="font-semibold text-gray-900">{{ $total }}</span></div>
                                        @if ($o->items->isNotEmpty())
                                            <ul class="mt-2 text-xs text-gray-600 list-disc list-inside">
                                                @foreach ($o->items as $it)
                                                    <li>{{ $it->title }} × {{ $it->qty }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        <div class="mt-3 flex items-center gap-2">
                                            <a href="{{ $ctaHref }}" class="inline-flex items-center rounded-md bg-sky-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-sky-700">{{ $ctaText }}</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <script>
            (function () {
                const tabInfo = document.getElementById('tab-info');
                const tabTrx = document.getElementById('tab-trx');
                const panelInfo = document.getElementById('panel-info');
                const panelTrx = document.getElementById('panel-trx');
                function activate(which) {
                    const isInfo = which === 'info';
                    if (isInfo) {
                        tabInfo.className = 'tab-btn active w-full bg-sky-50 px-3 py-3 text-sm font-medium text-sky-700 ring-1 ring-sky-200';
                        tabTrx.className = 'tab-btn w-full bg-gray-50 px-3 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-200 hover:bg-gray-100';
                        panelInfo.classList.remove('hidden');
                        panelTrx.classList.add('hidden');
                    } else {
                        tabTrx.className = 'tab-btn active w-full rounded-r-lg bg-sky-50 px-3 py-3 text-sm font-medium text-sky-700 ring-1 ring-sky-200';
                        tabInfo.className = 'tab-btn w-full rounded-r-lg bg-gray-50 px-3 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-200 hover:bg-gray-100';
                        panelTrx.classList.remove('hidden');
                        panelInfo.classList.add('hidden');
                    }
                }
                const params = new URLSearchParams(location.search);
                activate(params.get('tab') === 'trx' ? 'trx' : 'info');
                tabInfo.addEventListener('click', () => activate('info'));
                tabTrx.addEventListener('click', () => activate('trx'));
            })();
        </script>
    </main>
@endsection
