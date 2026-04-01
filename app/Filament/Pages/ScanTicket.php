<?php

namespace App\Filament\Pages;

use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ScanTicket extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationGroup = 'Tiket';
    protected static ?string $navigationLabel = 'Scan Ticket';
    protected static string $view = 'filament.pages.scan-ticket';

    public array $data = [];
    public ?array $result = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Kode Tiket')
                    ->placeholder('Scan atau ketik kode tiket')
                    ->autofocus()
                    ->autocomplete(false)
                    ->required()
                    ->suffixIcon('heroicon-m-qr-code'),
            ])
            ->statePath('data');
    }

    public function validateTicket(): void
    {
        $state = $this->form->getState();
        $code = trim((string) ($state['code'] ?? ''));
        $t = Ticket::query()->with(['event','order'])->where('code', $code)->first();

        if (! $t) {
            $this->result = ['status' => 'not_found'];
            $this->resetCode();
            return;
        }

        if ($t->status === 'issued') {
            $t->status = 'used';
            $t->used_at = now();
            $t->save();
            $this->result = ['status' => 'validated', 'ticket' => $t];
        } else {
            $this->result = ['status' => $t->status, 'ticket' => $t];
        }

        $this->resetCode();
    }

    protected function resetCode(): void
    {
        $this->data['code'] = '';
        $this->form->fill($this->data);
    }
}
