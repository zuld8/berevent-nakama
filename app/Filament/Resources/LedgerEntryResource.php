<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LedgerEntryResource\Pages;
use App\Models\LedgerEntry;
use App\Models\Campaign;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LedgerEntryResource extends Resource
{
    protected static ?string $model = LedgerEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Ledger Entries';

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                 Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Transaksi')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('wallet.owner_type')
                    ->label('Owner Type')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('wallet.owner.title')
                    ->label('Sumber Dana (Owner)')
                    ->getStateUsing(function ($record) {
                        $owner = optional($record->wallet)->owner;
                        return $owner->title ?? $owner->name ?? null;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('donor')
                    ->label('Donatur')
                    ->getStateUsing(function ($record) {
                        if ($record->source_type === \App\Models\Donation::class) {
                            $donation = $record->source; // morphTo
                            if (! $donation) return null;
                            return $donation->is_anonymous ? 'Anonim' : ($donation->donor_name ?: '—');
                        }
                        return null;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('source_campaign')
                    ->label('Campaign (Sumber)')
                    ->getStateUsing(function ($record) {
                        if ($record->source_type === \App\Models\Donation::class) {
                            return optional(optional($record->source)->campaign)->title;
                        }
                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => 'credit',
                        'danger' => 'debit',
                    ]),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 2, ',', '.')),
                // Tables\Columns\TextColumn::make('memo')->limit(40),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 2, ',', '.'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'credit' => 'Credit',
                        'debit' => 'Debit',
                    ]),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLedgerEntries::route('/'),
        ];
    }
}
