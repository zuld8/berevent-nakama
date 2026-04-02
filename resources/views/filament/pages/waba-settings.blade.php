<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button type="submit" color="primary">
                Simpan
            </x-filament::button>
            <x-filament::button type="button" color="gray" wire:click="testConnection">
                🧪 Test Koneksi
            </x-filament::button>
        </div>
    </form>

    <x-filament::section heading="Body Parameters" description="Variabel yang otomatis diisi sistem saat mengirim template">
        <div class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
            <p><strong>Order:</strong> nama, email, phone, reference, total, event_title, pay_url</p>
            <p><strong>Donasi:</strong> donor_name, donor_email, donor_phone, reference, amount, campaign_title, pay_url</p>
            <p class="mt-2 text-xs text-gray-400">Sesuaikan urutan body parameters di template WABA Anda dengan urutan yang dikirim sistem.</p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
