<x-filament::page>
    <div class="space-y-6" x-data="scanAttendance()">
        {{-- Instructions Card --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Cara Scan Attendance</h3>
                    <ol class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400 list-decimal list-inside">
                        <li>Pilih Event yang akan di-scan</li>
                        <li>Pilih Sesi/Materi yang sesuai</li>
                        <li>Scan atau input kode tiket peserta</li>
                        <li>Klik tombol Check-in untuk mencatat kehadiran</li>
                    </ol>
                </div>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form wire:submit="checkIn">
                {{ $this->form }}

                <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <x-filament::button
                        type="button"
                        color="gray"
                        class="w-full"
                        x-on:click="openScanner()"
                        icon="heroicon-m-qr-code"
                        icon-position="before"
                    >
                        Scan dengan Kamera
                    </x-filament::button>
                    <x-filament::button
                        type="submit"
                        size="lg"
                        class="w-full"
                        icon="heroicon-m-check-circle"
                        icon-position="before"
                    >
                        Check-in Attendance
                    </x-filament::button>
                </div>
            </form>
        </div>

        <!-- Camera Scan Modal -->
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/60" x-on:click="closeScanner()"></div>
            <div class="relative z-10 w-full max-w-2xl mx-auto rounded-lg border border-gray-200 bg-white p-4 shadow-xl dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start justify-between mb-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Scan Kode Tiket</h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" x-on:click="closeScanner()">✕</button>
                </div>
                <div class="mb-3 flex items-center gap-3">
                    <select class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" x-model="selectedCameraId">
                        <template x-for="cam in cameras" :key="cam.id">
                            <option :value="cam.id" x-text="cam.label || ('Camera ' + cam.id)"></option>
                        </template>
                    </select>
                    <x-filament::button size="sm" color="gray" type="button" x-on:click="start()" x-bind:disabled="isScanning">
                        Mulai
                    </x-filament::button>
                    <x-filament::button size="sm" color="danger" type="button" x-on:click="stop()" x-bind:disabled="!isScanning">
                        Berhenti
                    </x-filament::button>
                </div>
                <div id="qr-reader" wire:ignore class="w-full aspect-video bg-black/20 rounded-md overflow-hidden"></div>
                <p class="mt-3 text-xs text-gray-600 dark:text-gray-400">Arahkan kamera ke QR Code tiket. Setelah terbaca, sistem akan mengisi kode dan langsung check-in.</p>
            </div>
        </div>

        {{-- Result Card --}}
        @if($result)
            @php
                $st = $result['status'] ?? 'unknown';
                $t = $result['ticket'] ?? null;
            @endphp
            <div class="rounded-lg border-2 p-6 shadow-lg animate-in fade-in slide-in-from-top-4 duration-300
                @switch($st)
                    @case('checked_in')
                        border-green-500 bg-green-50 dark:border-green-600 dark:bg-green-950
                        @break
                    @case('already')
                        border-yellow-500 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-950
                        @break
                    @case('unpaid')
                    @case('wrong_event')
                    @case('not_found')
                    @case('invalid_input')
                        border-red-500 bg-red-50 dark:border-red-600 dark:bg-red-950
                        @break
                    @default
                        border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-900
                @endswitch
            ">
                <div class="flex items-start gap-4">
                    {{-- Icon --}}
                    <div class="flex-shrink-0">
                        @switch($st)
                            @case('checked_in')
                                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                @break
                            @case('already')
                                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                    </svg>
                                </div>
                                @break
                            @default
                                <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                        @endswitch
                    </div>

                    {{-- Content --}}
                    <div class="flex-1">
                        <h3 class="text-lg font-bold
                            @switch($st)
                                @case('checked_in')
                                    text-green-900 dark:text-green-100
                                    @break
                                @case('already')
                                    text-yellow-900 dark:text-yellow-100
                                    @break
                                @default
                                    text-red-900 dark:text-red-100
                            @endswitch
                        ">
                            @switch($st)
                                @case('checked_in')
                                    ✓ Check-in Berhasil!
                                    @break
                                @case('already')
                                    ⚠ Sudah Check-in
                                    @break
                                @case('unpaid')
                                    ✕ Order Belum Dibayar
                                    @break
                                @case('wrong_event')
                                    ✕ Event Tidak Sesuai
                                    @break
                                @case('not_found')
                                    ✕ Tiket Tidak Ditemukan
                                    @break
                                @default
                                    ✕ Input Tidak Valid
                            @endswitch
                        </h3>

                        <p class="mt-1 text-sm
                            @switch($st)
                                @case('checked_in')
                                    text-green-700 dark:text-green-300
                                    @break
                                @case('already')
                                    text-yellow-700 dark:text-yellow-300
                                    @break
                                @default
                                    text-red-700 dark:text-red-300
                            @endswitch
                        ">
                            @switch($st)
                                @case('checked_in')
                                    Peserta berhasil di-check-in untuk sesi ini.
                                    @break
                                @case('already')
                                    Tiket ini sudah pernah di-check-in untuk sesi yang dipilih.
                                    @break
                                @case('unpaid')
                                    Order untuk tiket ini belum dibayar. Silakan lakukan pembayaran terlebih dahulu.
                                    @break
                                @case('wrong_event')
                                    Tiket ini tidak berlaku untuk event yang dipilih.
                                    @break
                                @case('not_found')
                                    Kode tiket tidak ditemukan dalam sistem.
                                    @break
                                @default
                                    Silakan lengkapi semua field yang diperlukan.
                            @endswitch
                        </p>

                        @if($t)
                            <div class="mt-4 rounded-lg bg-white p-4 dark:bg-gray-800">
                                <div class="grid gap-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Kode Tiket:</span>
                                        <span class="rounded-md bg-gray-100 px-3 py-1.5 font-mono text-sm font-bold text-gray-900 dark:bg-gray-700 dark:text-gray-100">
                                            {{ $t->code }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Status Tiket:</span>
                                        <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium uppercase
                                            @if($t->status === 'valid')
                                                bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @else
                                                bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                            @endif
                                        ">
                                            {{ $t->status }}
                                        </span>
                                    </div>
                                    @if($t->order)
                                        <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                                            <div class="flex items-center justify-between">
                                                <span class="font-medium text-gray-700 dark:text-gray-300">No. Order:</span>
                                                <span class="text-gray-900 dark:text-gray-100">{{ $t->order->reference }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Status Order:</span>
                                            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium uppercase
                                                @if($t->order->status === 'paid')
                                                    bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @elseif($t->order->status === 'pending')
                                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                @else
                                                    bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                                @endif
                                            ">
                                                {{ $t->order->status }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament::page>

<script src="https://unpkg.com/html5-qrcode" defer></script>
<script>
    function scanAttendance() {
        return {
            open: false,
            html5QrCode: null,
            cameras: [],
            selectedCameraId: null,
            isScanning: false,

            async openScanner() {
                this.open = true;
                await this.$nextTick();
                await this.initScanner();
                await this.start();
            },
            async closeScanner() {
                await this.stop();
                this.open = false;
            },
            async initScanner() {
                try {
                    if (!window.Html5Qrcode) return;
                    const cams = await Html5Qrcode.getCameras();
                    this.cameras = cams || [];
                    if (!this.selectedCameraId && this.cameras.length) {
                        // Prefer back camera when possible
                        const back = this.cameras.find(c => /back|rear|environment/i.test(c.label || ''));
                        this.selectedCameraId = (back || this.cameras[0]).id;
                    }
                    if (!this.html5QrCode) {
                        this.html5QrCode = new Html5Qrcode('qr-reader', { verbose: false });
                    }
                } catch (e) {
                    console.error('Camera init error', e);
                }
            },
            async start() {
                try {
                    if (!this.html5QrCode) return;
                    if (this.isScanning) return;
                    const config = { fps: 10, qrbox: { width: 280, height: 280 } };
                    const cameraId = this.selectedCameraId ? { deviceId: { exact: this.selectedCameraId } } : { facingMode: 'environment' };
                    await this.html5QrCode.start(cameraId, config, (decodedText) => {
                        if (!decodedText) return;
                        this.$wire.set('data.code', String(decodedText).trim());
                        // Auto stop and submit
                        this.stop().then(() => {
                            this.open = false;
                            this.$wire.call('checkIn');
                        });
                    });
                    this.isScanning = true;
                } catch (e) {
                    console.error('Start scan error', e);
                }
            },
            async stop() {
                try {
                    if (this.html5QrCode && this.isScanning) {
                        await this.html5QrCode.stop();
                        await this.html5QrCode.clear();
                    }
                } catch (e) {
                    // ignore
                } finally {
                    this.isScanning = false;
                }
            },
        }
    }
</script>
