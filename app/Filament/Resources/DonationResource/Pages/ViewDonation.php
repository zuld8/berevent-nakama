<?php

namespace App\Filament\Resources\DonationResource\Pages;

use App\Filament\Resources\DonationResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewDonation extends ViewRecord
{
    protected static string $resource = DonationResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Informasi Donasi')
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
                            'pending', 'initiated' => 'warning',
                            'failed' => 'danger',
                            'refunded' => 'gray',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Tanggal Donasi')
                        ->dateTime('d M Y, H:i'),
                ]),

            Infolists\Components\Section::make('Campaign')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('campaign.title')
                        ->label('Campaign'),
                    Infolists\Components\TextEntry::make('campaign.organization.name')
                        ->label('Organisasi')
                        ->default('-'),
                ]),

            Infolists\Components\Section::make('Detail Donatur')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('donor_name')
                        ->label('Nama')
                        ->default('-'),
                    Infolists\Components\TextEntry::make('donor_email')
                        ->label('Email')
                        ->copyable()
                        ->default('-'),
                    Infolists\Components\TextEntry::make('donor_phone')
                        ->label('Telepon')
                        ->copyable()
                        ->default('-'),
                    Infolists\Components\IconEntry::make('is_anonymous')
                        ->label('Anonim')
                        ->boolean(),
                    Infolists\Components\TextEntry::make('message')
                        ->label('Pesan')
                        ->default('Tidak ada pesan')
                        ->columnSpan(2),
                ]),

            Infolists\Components\Section::make('Detail Pembayaran')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('amount')
                        ->label('Jumlah')
                        ->money('IDR')
                        ->weight('bold')
                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    Infolists\Components\TextEntry::make('currency')
                        ->label('Mata Uang')
                        ->default('IDR'),
                    Infolists\Components\TextEntry::make('paid_at')
                        ->label('Dibayar Pada')
                        ->dateTime('d M Y, H:i:s')
                        ->default('Belum dibayar')
                        ->color(fn ($state) => $state ? 'success' : 'danger'),
                ]),
        ]);
    }
}
