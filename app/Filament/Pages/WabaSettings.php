<?php

namespace App\Filament\Pages;

use App\Models\Organization;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Services\WabaService;

class WabaSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Integrasi';
    protected static ?string $navigationLabel = 'WABA - Setting';
    protected static ?int $navigationSort = 8;
    protected static ?string $slug = 'integrations/waba/settings';
    protected static ?string $title = 'WABA (WhatsApp Business API)';

    protected static string $view = 'filament.pages.waba-settings';

    public array $data = [];
    public ?Organization $org = null;

    public function mount(): void
    {
        $this->org = Organization::query()->first();
        $meta = $this->org?->meta_json ?? [];
        $cfg = data_get($meta, 'integrations.waba', []);

        $this->form->fill([
            'enabled'       => (bool)($cfg['enabled'] ?? false),
            'device_key'    => $cfg['device_key'] ?? '',
            'api_key'       => $cfg['api_key'] ?? '',
            'template_lang' => $cfg['template_lang'] ?? 'id',

            'template_welcome'   => $cfg['template_welcome'] ?? '',
            'template_paid'      => $cfg['template_paid'] ?? '',
            'template_followup1' => $cfg['template_followup1'] ?? '',
            'template_followup2' => $cfg['template_followup2'] ?? '',
            'template_followup3' => $cfg['template_followup3'] ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Koneksi WABA')
                    ->description('Koneksi ke Replai.id WhatsApp Business API')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Aktifkan WABA')
                            ->helperText('Jika aktif, tombol Follow-Up akan mengirim pesan WA via WABA.')
                            ->inline(false),
                        TextInput::make('device_key')
                            ->label('Device Key')
                            ->placeholder('YOUR_DEVICE_ID')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('api_key')
                            ->label('API Key')
                            ->placeholder('YOUR_API_KEY')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255),
                        Select::make('template_lang')
                            ->label('Template Language')
                            ->options([
                                'id' => 'Indonesia (id)',
                                'en_US' => 'English (en_US)',
                                'en' => 'English (en)',
                            ])
                            ->native(false)
                            ->required(),
                    ]),

                Section::make('Template IDs')
                    ->description('Masukkan ID template WABA yang sudah diapprove di Meta Business Suite. Body parameters akan diisi otomatis oleh sistem.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('template_welcome')
                            ->label('🟢 Welcome (W)')
                            ->placeholder('template_id_welcome')
                            ->helperText('Pesan pertama saat pesanan/donasi dibuat.')
                            ->maxLength(255),
                        TextInput::make('template_paid')
                            ->label('✅ Paid')
                            ->placeholder('template_id_paid')
                            ->helperText('Konfirmasi pembayaran berhasil.')
                            ->maxLength(255),
                        TextInput::make('template_followup1')
                            ->label('🟡 Follow-Up 1')
                            ->placeholder('template_id_followup1')
                            ->helperText('Reminder pertama untuk pembayaran.')
                            ->maxLength(255),
                        TextInput::make('template_followup2')
                            ->label('🟡 Follow-Up 2')
                            ->placeholder('template_id_followup2')
                            ->helperText('Reminder kedua.')
                            ->maxLength(255),
                        TextInput::make('template_followup3')
                            ->label('🟡 Follow-Up 3')
                            ->placeholder('template_id_followup3')
                            ->helperText('Reminder terakhir.')
                            ->maxLength(255),
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
            Action::make('test')
                ->label('🧪 Test Koneksi')
                ->color('gray')
                ->action('testConnection'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $meta = $this->org?->meta_json ?? [];
        data_set($meta, 'integrations.waba', [
            'enabled'       => (bool)($state['enabled'] ?? false),
            'device_key'    => $state['device_key'] ?? '',
            'api_key'       => $state['api_key'] ?? '',
            'template_lang' => $state['template_lang'] ?? 'id',

            'template_welcome'   => $state['template_welcome'] ?? '',
            'template_paid'      => $state['template_paid'] ?? '',
            'template_followup1' => $state['template_followup1'] ?? '',
            'template_followup2' => $state['template_followup2'] ?? '',
            'template_followup3' => $state['template_followup3'] ?? '',
        ]);

        $org = $this->org ?? new Organization();
        $org->meta_json = $meta;
        $org->save();
        $this->org = $org;

        Notification::make()
            ->title('Pengaturan WABA disimpan')
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        $state = $this->form->getState();
        $deviceKey = $state['device_key'] ?? '';
        $apiKey = $state['api_key'] ?? '';

        if (empty($deviceKey) || empty($apiKey)) {
            Notification::make()
                ->title('Device Key dan API Key harus diisi')
                ->danger()
                ->send();
            return;
        }

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(10)
                ->acceptJson()
                ->get('https://chat.replai.id/api-app/waba/device/info', [
                    'device_key' => $deviceKey,
                    'api_key' => $apiKey,
                ]);

            if ($res->successful()) {
                $body = $res->json();
                $status = $body['status'] ?? false;
                if ($status) {
                    Notification::make()
                        ->title('✅ Koneksi berhasil!')
                        ->body('Device terhubung.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('⚠️ Response tidak valid')
                        ->body($body['message'] ?? 'Unknown')
                        ->warning()
                        ->send();
                }
            } else {
                Notification::make()
                    ->title('❌ Gagal terhubung')
                    ->body('HTTP ' . $res->status())
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('❌ Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
