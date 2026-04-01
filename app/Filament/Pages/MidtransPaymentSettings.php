<?php

namespace App\Filament\Pages;

use App\Models\PaymentMethod;
use App\Services\Payments\PaymentMethodCatalog;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class MidtransPaymentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Integrasi';
    protected static ?string $navigationLabel = 'Pembayaran - Midtrans';
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'integrations/midtrans/methods';
    protected static ?string $title = 'Pengaturan Metode Midtrans';

    protected static string $view = 'filament.pages.midtrans-payment-settings';

    public array $data = [];

    public function mount(): void
    {
        // Ensure default methods are present
        $catalog = new PaymentMethodCatalog();
        $catalog->ensureMidtransRecords();

        $rows = PaymentMethod::query()
            ->where('provider', 'midtrans')
            ->orderBy('method_code')
            ->get();

        $defaults = $catalog->midtransDefaults();
        $items = [];
        foreach ($rows as $row) {
            $code = $row->method_code;
            if (! isset($defaults[$code])) continue;
            $cfg = $row->config_json ?? [];
            $items[] = [
                'id' => $row->id,
                'code' => $code,
                'name' => $defaults[$code]['name'] ?? strtoupper($code),
                'logo' => $defaults[$code]['logo'] ?? null,
                'active' => (bool) $row->active,
                'fee_type' => $cfg['fee_type'] ?? null,
                'fee_value' => $cfg['fee_value'] ?? null,
                'default_fee' => $defaults[$code]['fee'] ?? null,
            ];
        }

        $this->form->fill([
            'methods' => $items,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Midtrans — Metode Pembayaran')
                    ->description('Nyalakan / matikan dan atur admin fee. Biarkan kosong untuk mengikuti default Midtrans.')
                    ->schema([
                        Repeater::make('methods')
                            ->label('Metode')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->schema([
                                Placeholder::make('name')
                                    ->label('Nama')
                                    ->content(fn ($get) => $get('name') . ' (' . $get('code') . ')'),
                                Toggle::make('active')->label('Aktif')->inline(false),
                                Select::make('fee_type')
                                    ->label('Tipe Fee')
                                    ->options([
                                        'flat' => 'Flat (Rp)',
                                        'percent' => 'Persen (%)',
                                    ])
                                    ->native(false)
                                    ->placeholder('Default Midtrans')
                                    ->columnSpan(2),
                                TextInput::make('fee_value')
                                    ->label('Nilai Fee')
                                    ->numeric()
                                    ->placeholder('Kosongkan untuk default')
                                    ->columnSpan(2),
                                Placeholder::make('default_info')
                                    ->label('Default (referensi)')
                                    ->content(function ($get) {
                                        $d = (array) $get('default_fee');
                                        if (($d['type'] ?? '') === 'percent') {
                                            return 'Default: ' . rtrim(rtrim(number_format((float)($d['value'] ?? 0), 2, ',', '.'), '0'), ',') . '%';
                                        }
                                        return 'Default: Rp' . number_format((float)($d['value'] ?? 0), 0, ',', '.');
                                    })
                                    ->helperText('Hanya informasi, tidak disimpan')
                                    ->columnSpanFull(),
                            ])
                            ->columns(6)
                            ->grid(1),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('simpan')
                ->label('Simpan')
                ->submit('save')
                ->color('primary'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $items = (array) ($state['methods'] ?? []);

        foreach ($items as $row) {
            $id = (int) ($row['id'] ?? 0);
            if (! $id) continue;
            $model = PaymentMethod::find($id);
            if (! $model) continue;

            $model->active = (bool) ($row['active'] ?? false);

            $type = $row['fee_type'] ?? null;
            $value = $row['fee_value'] ?? null;
            if ($type && $value !== null && $value !== '') {
                $model->config_json = [
                    'fee_type' => $type,
                    'fee_value' => (float) $value,
                ];
            } else {
                $model->config_json = null; // follow default
            }
            $model->save();
        }

        Notification::make()->title('Pengaturan Midtrans disimpan')->success()->send();
    }
}

