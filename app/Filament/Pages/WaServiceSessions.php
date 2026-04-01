<?php

namespace App\Filament\Pages;

use App\Services\WaService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\WaServiceSession as WaServiceSessionModel;
use App\Models\Organization;

class WaServiceSessions extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Integrasi';
    protected static ?string $navigationLabel = 'WA Service - Session';
    protected static ?int $navigationSort = 11;
    protected static ?string $slug = 'integrations/wa-service/sessions';
    protected static ?string $title = 'WA Service - Sessions';

    protected static string $view = 'filament.pages.wa-service-sessions';

    /** @var array<int, array<string, mixed>> */
    public array $records = [];

    public function mount(): void
    {
        $this->loadRecords();
    }

    public function loadRecords(): void
    {
        $svc = new WaService();
        $this->records = $svc->listAccounts();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createSession')
                ->label('Buat Session')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('clientId')
                        ->label('Client ID / Nomor')
                        ->placeholder('6285xxxxxxxxxx')
                        ->required()
                        ->maxLength(30),
                ])
                ->action(function (array $data): void {
                    $clientId = trim((string)($data['clientId'] ?? ''));
                    if ($clientId === '') {
                        Notification::make()->title('Client ID wajib diisi')->danger()->send();
                        return;
                    }

                    try {
                        $svc = new WaService();
                        $created = $svc->startAccount($clientId);
                        $this->loadRecords();

                        if (! empty($created)) {
                            $status = strtoupper((string)($created['status'] ?? '')); 
                            Notification::make()
                                ->title('Session dibuat')
                                ->body("Client {$clientId} status: {$status}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tidak ada respon pembuatan session')
                                ->warning()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal membuat session')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->loadRecords()),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('clientId')
                ->label('Client')
                ->wrap(),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(function ($state) {
                    $status = strtoupper((string) $state);
                    return match (true) {
                        in_array($status, ['READY', 'CONNECTED'], true) => 'success',
                        $status === 'INITIALIZING' => 'warning',
                        $status === 'DISCONNECTED' => 'danger',
                        default => 'gray',
                    };
                }),
            Tables\Columns\TextColumn::make('lastConnectedAt')
                ->label('Last Connected')
                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->toDateTimeString() : '—'),
            Tables\Columns\TextColumn::make('lastMessageAt')
                ->label('Last Message')
                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->toDateTimeString() : '—'),
            Tables\Columns\TextColumn::make('updatedAt')
                ->label('Updated')
                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->toDateTimeString() : '—'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            TableAction::make('reconnect')
                ->label('Reconnect')
                ->icon('heroicon-o-arrow-path')
                ->tooltip('Coba reconnect session untuk client ini')
                ->action(function (WaServiceSessionModel $record): void {
                    $clientId = (string) $record->clientId;
                    try {
                        $svc = new WaService();
                        $res = $svc->reconnectAccount($clientId);
                        $this->loadRecords();
                        if (! empty($res)) {
                            $status = strtoupper((string)($res['status'] ?? ''));
                            Notification::make()
                                ->title('Reconnect berhasil')
                                ->body("Client {$clientId} status: {$status}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Reconnect tidak mengembalikan data')
                                ->warning()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal reconnect')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            TableAction::make('qr')
                ->label('Lihat QR')
                ->icon('heroicon-o-qr-code')
                ->modalHeading(fn (WaServiceSessionModel $record) => "QR untuk {$record->clientId}")
                ->modalCancelActionLabel('Tutup')
                ->modalContent(function (WaServiceSessionModel $record) {
                    $svc = new WaService();
                    $qrRes = $svc->getQr((string) $record->clientId);
                    $qr = $qrRes['qr'] ?? '';
                    return view('filament.pages.partials.wa-qr', [
                        'clientId' => (string) $record->clientId,
                        'qr' => (string) $qr,
                    ]);
                }),
            TableAction::make('delete')
                ->label('Hapus')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Session')
                ->modalDescription(fn (WaServiceSessionModel $record) => "Hapus session untuk client {$record->clientId}?")
                ->modalSubmitActionLabel('Hapus')
                ->action(function (WaServiceSessionModel $record): void {
                    $clientId = (string) $record->clientId;
                    try {
                        $svc = new WaService();
                        $ok = $svc->deleteAccount($clientId);
                        $this->loadRecords();
                        if ($ok) {
                            Notification::make()->title('Session dihapus')->success()->send();
                        } else {
                            Notification::make()->title('Gagal menghapus session')->warning()->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal menghapus session')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Provide a harmless query on an existing table to satisfy Filament.
        // Actual records are supplied via getTableRecords().
        return Organization::query()->whereRaw('0 = 1');
    }

    public function getTableRecord(?string $key): ?Model
    {
        if ($key === null) {
            return null;
        }

        $records = $this->getTableRecords();

        if ($records instanceof \Illuminate\Contracts\Pagination\Paginator || $records instanceof \Illuminate\Contracts\Pagination\CursorPaginator) {
            $items = collect($records->items());
        } elseif ($records instanceof EloquentCollection) {
            $items = $records;
        } else {
            $items = collect();
        }

        foreach ($items as $record) {
            if ((string) $this->getTableRecordKey($record) === (string) $key) {
                return $record;
            }
        }

        return null;
    }

    public function getTableRecords(): EloquentCollection | PaginatorContract | CursorPaginatorContract
    {
        $items = array_map(fn (array $row) => new WaServiceSessionModel($row), $this->records);

        $perPage = $this->getTableRecordsPerPage();
        if ($perPage === 'all' || $perPage === null) {
            return new EloquentCollection($items);
        }

        $page = max(1, (int) $this->getTablePage());
        $perPage = (int) $perPage;
        $total = count($items);
        $slice = array_slice($items, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $this->getTablePaginationPageName(),
            ],
        );
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'Belum ada session yang dapat ditampilkan';
    }

    public function getTableRecordKey(Model $record): string
    {
        $id = $record->getAttribute('id') ?? $record->getAttribute('clientId');
        return (string) ($id ?? md5(json_encode($record->getAttributes())));
    }
}
