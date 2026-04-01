<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;

class FactoryReset extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Manajemen';
    protected static ?string $navigationLabel = 'Factory Reset';
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.factory-reset';

    public ?bool $wipePaymentConfig = false;
    public ?bool $clearCategories = false;
    public ?bool $purgeFiles = false;
    public ?string $confirm = '';

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return false;
    }


    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Konfirmasi')
                    ->schema([
                        Forms\Components\Toggle::make('wipePaymentConfig')->label('Hapus konfigurasi channel/metode pembayaran'),
                        Forms\Components\Toggle::make('clearCategories')->label('Hapus kategori'),
                        Forms\Components\Toggle::make('purgeFiles')->label('Hapus file upload (articles/* di S3)'),
                    ]),
            ])
            ->statePath('.');
    }

    public function runReset(): void
    {
        if (strtoupper(trim((string) $this->confirm)) !== 'RESET') {
            Notification::make()->title('Ketik "RESET" untuk konfirmasi')->danger()->send();
            return;
        }
        $code = Artisan::call('app:factory-reset', [
            '--yes' => true,
            '--wipe-payment-config' => (int) ($this->wipePaymentConfig ?? false),
            '--clear-categories' => (int) ($this->clearCategories ?? false),
            '--purge-files' => (int) ($this->purgeFiles ?? false),
        ]);

        if ($code === 0) {
            Notification::make()->title('Factory reset berhasil')->success()->send();
            $this->confirm = '';
            // Close confirmation modal
            $this->dispatch('close-modal', id: 'confirm-reset');
        } else {
            Notification::make()->title('Factory reset gagal')->danger()->send();
        }
    }
}
