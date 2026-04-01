<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventMaterial;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class ScanAttendance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Tiket';
    protected static ?string $navigationLabel = 'Scan Attendance';
    protected static string $view = 'filament.pages.scan-attendance';

    public ?array $data = [];
    public ?array $result = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('event_id')
                ->label('Event')
                ->options(fn () => Event::query()->orderBy('title')->pluck('title', 'id'))
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (Set $set) {
                    $set('material_id', null);
                    $set('code', '');
                })
                ->required(),
            Forms\Components\Select::make('material_id')
                ->label('Sesi/Materi')
                ->options(function (Get $get) {
                    $evId = (int) ($get('event_id') ?? 0);
                    if (!$evId) return [];
                    return EventMaterial::query()
                        ->where('event_id', $evId)
                        ->orderBy('date_at')
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(function ($m) {
                            $date = optional($m->date_at)->format('d M Y');
                            return [$m->id => ($m->title . ($date ? ' — ' . $date : ''))];
                        })->toArray();
                })
                ->searchable()
                ->preload()
                ->visible(fn (Get $get) => (int) ($get('event_id') ?? 0) > 0)
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('code', ''))
                ->required()
                ->placeholder('Pilih sesi/materi'),
            Forms\Components\TextInput::make('code')
                ->label('Kode Tiket')
                ->placeholder('Scan atau ketik kode tiket')
                ->autofocus()
                ->autocomplete(false)
                ->visible(fn (Get $get) => (int) ($get('material_id') ?? 0) > 0)
                ->required()
                ->suffixIcon('heroicon-m-qr-code'),
        ];
    }

    public function checkIn(): void
    {
        $this->result = null;

        $data = $this->form->getState();

        $code = trim((string) ($data['code'] ?? ''));
        $eventId = (int) ($data['event_id'] ?? 0);
        $materialId = (int) ($data['material_id'] ?? 0);

        if ($code === '' || !$eventId || !$materialId) {
            $this->result = ['status' => 'invalid_input'];
            Notification::make()
                ->danger()
                ->title('Input Tidak Valid')
                ->body('Silakan lengkapi semua field.')
                ->send();
            return;
        }

        $ticket = Ticket::query()->with(['order'])->where('code', $code)->first();

        if (!$ticket) {
            $this->result = ['status' => 'not_found'];
            Notification::make()
                ->danger()
                ->title('Tiket Tidak Ditemukan')
                ->body("Kode tiket '{$code}' tidak ditemukan dalam sistem.")
                ->send();
            $this->resetCode();
            return;
        }

        if ((int) $ticket->event_id !== $eventId) {
            $this->result = ['status' => 'wrong_event', 'ticket' => $ticket];
            Notification::make()
                ->danger()
                ->title('Event Tidak Sesuai')
                ->body('Tiket ini tidak untuk event yang dipilih.')
                ->send();
            $this->resetCode();
            return;
        }

        // Check if order is paid
        if (($ticket->order?->status ?? '') !== 'paid') {
            $this->result = ['status' => 'unpaid', 'ticket' => $ticket];
            Notification::make()
                ->warning()
                ->title('Order Belum Dibayar')
                ->body('Order untuk tiket ini belum dibayar.')
                ->send();
            $this->resetCode();
            return;
        }

        // Check for duplicate entry
        $exists = Attendance::query()
            ->where('ticket_id', $ticket->id)
            ->where('material_id', $materialId)
            ->exists();

        if ($exists) {
            $this->result = ['status' => 'already', 'ticket' => $ticket];
            Notification::make()
                ->warning()
                ->title('Sudah Check-in')
                ->body('Tiket ini sudah di-check-in untuk sesi ini.')
                ->send();
            $this->resetCode();
            return;
        }

        // Create attendance record
        Attendance::create([
            'ticket_id' => $ticket->id,
            'event_id' => $eventId,
            'material_id' => $materialId,
            'checked_in_at' => now(),
        ]);

        $this->result = ['status' => 'checked_in', 'ticket' => $ticket];

        Notification::make()
            ->success()
            ->title('Check-in Berhasil')
            ->body("Tiket {$ticket->code} berhasil di-check-in.")
            ->send();

        $this->resetCode();
    }

    protected function resetCode(): void
    {
        $this->data['code'] = '';
        $this->form->fill($this->data);
    }
}
