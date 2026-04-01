<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\TicketsRelationManager;
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
                Tables\Columns\TextColumn::make('reference')->label('Ref')->searchable()->copyable()->copyMessage('Copied'),
                Tables\Columns\TextColumn::make('user.name')->label('User')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total')
                    ->money('IDR', false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('meta_json->payment_type')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'automatic' ? 'Automatic' : 'Manual')
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
                Tables\Columns\TextColumn::make('paid_at')->label('Paid At')->dateTime()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'failed' => 'Failed',
                    'cancelled' => 'Cancelled',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('reviewManual')
                    ->label('Review Manual')
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

                        // Issue tickets upon manual approval
                        try { \App\Services\TicketIssuer::issueForOrder($record); } catch (\Throwable $e) { }

                        \Filament\Notifications\Notification::make()->title('Order disetujui & tiket diterbitkan')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ])
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }
}
