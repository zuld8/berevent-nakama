<x-filament-panels::page>
    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
        <h2 class="mb-2 text-lg font-semibold">Peringatan</h2>
        <p>Aksi ini akan menghapus data aplikasi (campaign, donasi, payout, wallet, ledger, dsb) dan mempertahankan Organizations serta Users. Lanjutkan dengan hati-hati.</p>
    </div>

    <div class="mt-6">
        <form class="space-y-4">
            {{ $this->form }}
            <x-filament::button type="button" color="danger" wire:click="$dispatch('open-modal', { id: 'confirm-reset' })">
                Factory Reset
            </x-filament::button>
        </form>
    </div>

    <x-filament::modal id="confirm-reset">
        <x-slot name="heading">Konfirmasi Reset</x-slot>
        <div class="space-y-4">
            <p>Input "RESET" untuk melanjutkan. Tindakan ini tidak dapat dibatalkan.</p>
            <x-filament::input
                type="text"
                wire:model.defer="confirm"
                placeholder="Ketik RESET"
            />
        </div>
        <x-slot name="footer">
            <x-filament::button wire:click="runReset" color="danger">Ya, Reset</x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
