<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DonationResource\Pages;
use App\Models\Donation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
            Forms\Components\Select::make('campaign_id')
                ->relationship('campaign', 'title')
                ->label('Campaign')
                ->required(),
            Forms\Components\TextInput::make('donor_name')
                ->label('Nama Donatur')
                ->maxLength(255),
            Forms\Components\TextInput::make('donor_phone')
                ->label('No. HP')
                ->maxLength(30),
            Forms\Components\TextInput::make('donor_email')
                ->label('Email')
                ->email()
                ->maxLength(255),
            Forms\Components\TextInput::make('amount')
                ->label('Jumlah')
                ->numeric()
                ->prefix('Rp')
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
            Forms\Components\Toggle::make('is_anonymous')
                ->label('Anonim'),
            Forms\Components\Textarea::make('message')
                ->label('Pesan')
                ->maxLength(255),
            Forms\Components\TextInput::make('reference')
                ->label('Referensi')
                ->disabled(),
            Forms\Components\DateTimePicker::make('paid_at')
                ->label('Tanggal Bayar'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Ref')
                    ->searchable()
                    ->sortable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->limit(20)
                    ->sortable(),
                Tables\Columns\TextColumn::make('donor_name')
                    ->label('Donatur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
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
                    ->placeholder('—'),
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
                    ->relationship('campaign', 'title'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'edit' => Pages\EditDonation::route('/{record}/edit'),
        ];
    }
}
