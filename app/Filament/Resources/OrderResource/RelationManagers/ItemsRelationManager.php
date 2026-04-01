<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Items';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Title')->wrap(),
                Tables\Columns\TextColumn::make('qty')->label('Qty'),
                Tables\Columns\TextColumn::make('unit_price')->label('Unit Price')->money('IDR', false),
                Tables\Columns\TextColumn::make('subtotal')->label('Subtotal')
                    ->state(fn ($record) => (float)$record->unit_price * (int)$record->qty)
                    ->money('IDR', false),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
