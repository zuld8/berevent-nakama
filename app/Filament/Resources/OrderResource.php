<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\TicketsRelationManager;
use App\Filament\Resources\OrderResource\Widgets\OrderStatsOverview;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->label('Ref')
                    ->searchable()->copyable()->copyMessage('Copied')
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                Tables\Columns\TextColumn::make('user.name')->label('Pembeli')
                    ->description(fn ($record) => $record->user?->email)
                    ->sortable()->searchable(),
                Tables\Columns\TextColumn::make('items_summary')
                    ->label('Event')
                    ->state(fn ($record) => $record->items->pluck('title')->implode(', '))
                    ->wrap()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total')
                    ->money('IDR', false)
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('meta_json->payment_type')
                    ->label('Bayar')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'automatic' ? 'Otomatis' : 'Manual')
                    ->colors([
                        'warning' => 'manual',
                        'success' => 'automatic',
                    ])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->colors([
                    'gray' => 'pending',
                    'success' => 'paid',
                    'danger' => 'failed',
                    'warning' => 'cancelled',
                ]),
                Tables\Columns\TextColumn::make('paid_at')->label('Dibayar')
                    ->dateTime('d M Y H:i')
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')
                    ->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'failed' => 'Failed',
                    'cancelled' => 'Cancelled',
                ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('reviewManual')
                    ->label('Review')
                    ->icon('heroicon-o-check')
                    ->color('warning')
                    ->visible(fn ($record) => ($record->status === 'pending')
                        && (data_get($record->meta_json, 'payment_type') === 'manual'))
                    ->modalHeading('Bukti Pembayaran Manual')
                    ->modalSubmitActionLabel('Approve')
                    ->modalCancelActionLabel('Batal')
                    ->modalContent(function ($record) {
                        $path = (string) data_get($record->meta_json, 'manual.proof_path');
                        $url = null;
                        if ($path) {
                            try { $url = \Illuminate\Support\Facades\Storage::disk(media_disk())->temporaryUrl($path, now()->addMinutes(10)); }
                            catch (\Throwable) { $url = \Illuminate\Support\Facades\Storage::disk(media_disk())->url($path); }
                        }
                        return view('filament.orders.manual-proof', ['url' => $url, 'record' => $record]);
                    })
                    ->form([
                        Forms\Components\Textarea::make('note')->label('Catatan (opsional)')->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $meta = $record->meta_json ?? [];
                        $meta['manual'] = ($meta['manual'] ?? []) + [
                            'status' => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now()->toISOString(),
                            'note' => $data['note'] ?? null,
                        ];
                        $record->meta_json = $meta;
                        $record->status = 'paid';
                        $record->paid_at = now();
                        $record->save();

                        try { \App\Services\TicketIssuer::issueForOrder($record); } catch (\Throwable $e) { }

                        \Filament\Notifications\Notification::make()->title('Order disetujui & tiket diterbitkan')->success()->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            TicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            OrderStatsOverview::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'items']);
    }
}
