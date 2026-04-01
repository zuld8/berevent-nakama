<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';
    protected static ?string $title = 'Tickets';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Code')->copyable(),
                Tables\Columns\TextColumn::make('event.title')->label('Event')->wrap(),
                Tables\Columns\TextColumn::make('status')->badge()->colors([
                    'success' => 'issued',
                    'warning' => 'used',
                    'danger' => 'void',
                ]),
                Tables\Columns\TextColumn::make('used_at')->label('Used At')->dateTime(),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime()->toggleable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('markUsed')
                    ->label('Mark as Used')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'used')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->status = 'used';
                        $record->used_at = now();
                        $record->save();
                    }),
                Tables\Actions\Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'void')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->status = 'void';
                        $record->save();
                    }),
            ])
            ->bulkActions([]);
    }
}

