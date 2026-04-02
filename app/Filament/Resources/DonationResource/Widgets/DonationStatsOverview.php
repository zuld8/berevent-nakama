<?php

namespace App\Filament\Resources\DonationResource\Widgets;

use App\Models\Donation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DonationStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $total = Donation::count();
        $paid = Donation::where('status', 'paid')->count();
        $pending = Donation::whereIn('status', ['initiated', 'pending'])->count();
        $paidAmount = Donation::where('status', 'paid')->sum('amount');
        $ratio = $total > 0 ? round(($paid / $total) * 100) : 0;

        return [
            Stat::make('Total Donasi', number_format($total))
                ->description('Semua donasi masuk')
                ->descriptionIcon('heroicon-m-heart')
                ->color('primary'),
            Stat::make('Total Terkumpul', 'Rp ' . number_format($paidAmount, 0, ',', '.'))
                ->description($paid . ' donasi terbayar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Rasio Bayar', $ratio . '%')
                ->description('Konversi pembayaran')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($ratio >= 70 ? 'success' : ($ratio >= 40 ? 'warning' : 'danger')),
            Stat::make('Belum Dibayar', number_format($pending))
                ->description('Donasi pending')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'success'),
        ];
    }
}
