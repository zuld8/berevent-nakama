<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DonationResource\Pages;
use App\Filament\Resources\DonationResource\Widgets\DonationStatsOverview;
use App\Filament\Columns\FollowUpColumn;
use App\Models\Donation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DonationResource extends Resource
{
    protected static ?string $model = Donation::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Donasi';
    protected static ?string $navigationLabel = 'Donasi Masuk';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Donasi')->columns(2)->schema([
                Forms\Components\Select::make('campaign_id')
                    ->relationship('campaign', 'title')
                    ->label('Campaign')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'initiated' => 'Initiated',
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'process' => 'Process',
                        'complete' => 'Complete',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                Forms\Components\TextInput::make('reference')
                    ->label('Referensi')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Tanggal Bayar'),
                Forms\Components\Toggle::make('is_anonymous')
                    ->label('Anonim'),
            ]),
            Forms\Components\Section::make('Info Donatur')->columns(2)->schema([
                Forms\Components\TextInput::make('donor_name')
                    ->label('Nama Donatur')
                    ->maxLength(255),
                Forms\Components\TextInput::make('donor_email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('donor_phone')
                    ->label('No. HP')
                    ->maxLength(30),
                Forms\Components\Textarea::make('message')
                    ->label('Pesan')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Ref')
                    ->searchable()
                    ->copyable()->copyMessage('Copied')
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                Tables\Columns\TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->limit(25)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('donor_name')
                    ->label('Donatur')
                    ->description(fn ($record) => $record->is_anonymous ? '(Anonim)' : $record->donor_email)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'initiated',
                        'info' => 'pending',
                        'success' => 'paid',
                        'danger' => 'failed',
                        'gray' => 'refunded',
                        'primary' => 'process',
                    ]),
                FollowUpColumn::make('followup')
                    ->label('Follow-Up'),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Dibayar')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'initiated' => 'Initiated',
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('campaign')
                    ->relationship('campaign', 'title')
                    ->searchable()
                    ->preload(),
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
                            unset($followups[$key]);
                            $meta['followups'] = $followups;
                            $record->meta_json = $meta;
                            $record->save();
                            \Filament\Notifications\Notification::make()
                                ->title('Follow-up dibatalkan')
                                ->warning()->send();
                            return;
                        }

                        $phone = $record->donor_phone ?? '';

                        $bodyParams = [
                            $record->donor_name ?? 'Donatur',
                            $record->reference ?? '',
                            'Rp ' . number_format((float)$record->amount, 0, ',', '.'),
                            $record->campaign?->title ?? '-',
                        ];

                        $waba = new \App\Services\WabaService();
                        $config = $waba->getConfig();
                        $sent = false;
                        $error = null;

                        if ($config['enabled'] && !empty($phone)) {
                            $result = $waba->sendFollowUp($key, $phone, $bodyParams);
                            $sent = $result['success'];
                            $error = $result['error'];
                        }

                        $followups[$key] = now()->format('d M Y H:i');
                        if ($sent) {
                            $followups[$key . '_waba'] = true;
                        }
                        $meta['followups'] = $followups;
                        $record->meta_json = $meta;
                        $record->save();

                        $label = $key === 'w' ? 'Welcome' : 'Follow-up ' . str_replace('fu', '', $key);
                        if ($sent) {
                            \Filament\Notifications\Notification::make()
                                ->title("✅ {$label} terkirim via WABA")
                                ->success()->send();
                        } elseif (!empty($phone) && $config['enabled']) {
                            \Filament\Notifications\Notification::make()
                                ->title("⚠️ {$label} ditandai, tapi WA gagal")
                                ->body($error)
                                ->warning()->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title("📌 {$label} ditandai (WA tidak aktif/no HP kosong)")
                                ->info()->send();
                        }
                    }),

                Tables\Actions\EditAction::make()->iconButton(),

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
                            \Filament\Notifications\Notification::make()->title('Donasi marked as paid')->success()->send();
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
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonations::route('/'),
            'view' => Pages\ViewDonation::route('/{record}'),
            'edit' => Pages\EditDonation::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            DonationStatsOverview::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['campaign', 'user']);
    }
}
