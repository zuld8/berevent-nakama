<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\TicketsRelationManager;
use App\Filament\Resources\OrderResource\Widgets\OrderStatsOverview;
use App\Filament\Columns\FollowUpColumn;
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
                Tables\Columns\TextColumn::make('total_amount')->label('Total')
                    ->money('IDR', false)
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')->badge()->colors([
                    'gray' => 'pending',
                    'success' => 'paid',
                    'danger' => 'failed',
                    'warning' => 'cancelled',
                    'info' => 'process',
                    'primary' => 'complete',
                ]),
                FollowUpColumn::make('followup')
                    ->label('Follow-Up'),
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
                    'process' => 'Process',
                    'complete' => 'Complete',
                    'failed' => 'Failed',
                    'cancelled' => 'Cancelled',
                    'refunded' => 'Refunded',
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
                Tables\Actions\ViewAction::make()->iconButton(),

                // Toggle Follow-Up (hidden action triggered by column buttons)
                Tables\Actions\Action::make('toggleFollowUp')
                    ->hidden()
                    ->action(function ($record, array $arguments) {
                        $key = $arguments['key'] ?? null;
                        if (! $key || ! in_array($key, ['w', 'fu1', 'fu2', 'fu3'])) return;

                        $meta = $record->meta_json ?? [];
                        $followups = $meta['followups'] ?? [];

                        if (!empty($followups[$key])) {
                            // Toggle off
                            unset($followups[$key]);
                        } else {
                            // Toggle on
                            $followups[$key] = now()->format('d M Y H:i');
                        }

                        $meta['followups'] = $followups;
                        $record->meta_json = $meta;
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title($key === 'w' ? 'Welcome message toggled' : 'Follow-up ' . str_replace('fu', '', $key) . ' toggled')
                            ->success()->send();
                    }),

                // Review Manual Payment
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

                // Tindakan dropdown
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('markPending')
                        ->label('Mark As Pending')
                        ->icon('heroicon-o-clock')
                        ->color('gray')
                        ->visible(fn ($record) => $record->status !== 'pending')
                        ->requiresConfirmation()
                        ->action(fn ($record) => tap($record)->update(['status' => 'pending'])),
                    Tables\Actions\Action::make('markProcess')
                        ->label('Mark As Process')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->color('info')
                        ->visible(fn ($record) => $record->status !== 'process')
                        ->requiresConfirmation()
                        ->action(fn ($record) => tap($record)->update(['status' => 'process'])),
                    Tables\Actions\Action::make('markComplete')
                        ->label('Mark As Complete')
                        ->icon('heroicon-o-check-circle')
                        ->color('primary')
                        ->visible(fn ($record) => $record->status !== 'complete')
                        ->requiresConfirmation()
                        ->action(fn ($record) => tap($record)->update(['status' => 'complete'])),
                    Tables\Actions\Action::make('markPaid')
                        ->label('Mark As Paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->visible(fn ($record) => $record->status !== 'paid')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->status = 'paid';
                            $record->paid_at = now();
                            $record->save();
                            try { \App\Services\TicketIssuer::issueForOrder($record); } catch (\Throwable $e) { }
                            \Filament\Notifications\Notification::make()->title('Order marked as paid')->success()->send();
                        }),
                    Tables\Actions\Action::make('markUnpaid')
                        ->label('Mark As Unpaid')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'paid')
                        ->requiresConfirmation()
                        ->action(fn ($record) => tap($record)->update(['status' => 'pending', 'paid_at' => null])),
                    Tables\Actions\Action::make('markRefund')
                        ->label('Mark As Refund')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($record) => tap($record)->update(['status' => 'refunded'])),
                    Tables\Actions\Action::make('markCancel')
                        ->label('Mark As Cancel')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($record) => tap($record)->update(['status' => 'cancelled'])),
                ])
                    ->label('Tindakan')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('primary')
                    ->button(),
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
