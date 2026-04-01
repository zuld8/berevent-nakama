<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Payments';
    protected static ?int $navigationSort = 11;

     protected static bool $shouldRegisterNavigation = false;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('transaction_id')->label('Txn ID')->sortable(),
                Tables\Columns\TextColumn::make('method.provider')->label('Provider')->toggleable(),
                Tables\Columns\TextColumn::make('method.method_code')->label('Method')->toggleable(),
                Tables\Columns\TextColumn::make('provider_status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('gross_amount')->label('Gross')->money('IDR', false)->sortable(),
                Tables\Columns\TextColumn::make('fee_amount')->label('Fee')->money('IDR', false),
                Tables\Columns\TextColumn::make('net_amount')->label('Net')->money('IDR', false),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider_status')->options([
                    'initiated' => 'Initiated',
                    'pending' => 'Pending',
                    'settlement' => 'Settlement',
                    'capture' => 'Capture',
                    'paid' => 'Paid',
                    'failed' => 'Failed',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
