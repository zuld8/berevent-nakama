<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DonationResource\Pages;
use App\Filament\Resources\DonationResource\Widgets\DonationStatsOverview;
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
                    ]),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
