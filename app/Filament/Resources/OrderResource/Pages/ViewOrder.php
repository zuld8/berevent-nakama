<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Informasi Order')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('reference')
                        ->label('Reference')
                        ->copyable()
                        ->weight('bold'),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'paid' => 'success',
                            'pending' => 'warning',
                            'failed' => 'danger',
                            'cancelled' => 'gray',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Tanggal Order')
                        ->dateTime('d M Y, H:i'),
                ]),

            Infolists\Components\Section::make('Detail Pembeli')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')
                        ->label('Nama'),
                    Infolists\Components\TextEntry::make('user.email')
                        ->label('Email')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('user.phone')
                        ->label('Telepon')
                        ->copyable()
                        ->default('-'),
                ]),

            Infolists\Components\Section::make('Detail Pembayaran')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('total_amount')
                        ->label('Total')
                        ->money('IDR')
                        ->weight('bold')
                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    Infolists\Components\TextEntry::make('meta_json.payment_type')
                        ->label('Metode Bayar')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state === 'automatic' ? 'Automatic (Midtrans)' : 'Manual Transfer')
                        ->color(fn ($state) => $state === 'automatic' ? 'info' : 'warning'),
                    Infolists\Components\TextEntry::make('paid_at')
                        ->label('Dibayar Pada')
                        ->dateTime('d M Y, H:i:s')
                        ->default('Belum dibayar')
                        ->color(fn ($state) => $state ? 'success' : 'danger'),
                ]),

            Infolists\Components\Section::make('Items')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            Infolists\Components\TextEntry::make('title')
                                ->label('Event'),
                            Infolists\Components\TextEntry::make('qty')
                                ->label('Qty'),
                            Infolists\Components\TextEntry::make('unit_price')
                                ->label('Harga Satuan')
                                ->money('IDR'),
                            Infolists\Components\TextEntry::make('subtotal')
                                ->label('Subtotal')
                                ->state(fn ($record) => (float) $record->unit_price * (int) $record->qty)
                                ->money('IDR'),
                        ])
                        ->columns(4),
                ]),

            Infolists\Components\Section::make('Tiket')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('tickets')
                        ->label('')
                        ->schema([
                            Infolists\Components\TextEntry::make('code')
                                ->label('Kode Tiket')
                                ->copyable()
                                ->weight('bold'),
                            Infolists\Components\TextEntry::make('event.title')
                                ->label('Event'),
                            Infolists\Components\TextEntry::make('status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'active' => 'success',
                                    'used' => 'gray',
                                    'cancelled' => 'danger',
                                    default => 'warning',
                                }),
                        ])
                        ->columns(3),
                ])
                ->visible(fn ($record) => $record->tickets->count() > 0),
        ]);
    }
}
