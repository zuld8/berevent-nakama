<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $total = Order::count();
        $paid = Order::where('status', 'paid')->count();
        $pending = Order::where('status', 'pending')->count();
        $paidAmount = Order::where('status', 'paid')->sum('total_amount');
        $ratio = $total > 0 ? round(($paid / $total) * 100) : 0;

        return [
            Stat::make('Total Order', number_format($total))
                ->description('Semua order')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),
            Stat::make('Total Bayar', 'Rp ' . number_format($paidAmount, 0, ',', '.'))
                ->description($paid . ' order dibayar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Rasio Bayar', $ratio . '%')
                ->description('Konversi pembayaran')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($ratio >= 70 ? 'success' : ($ratio >= 40 ? 'warning' : 'danger')),
            Stat::make('Belum Dibayar', number_format($pending))
                ->description('Order pending')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'success'),
        ];
    }
}
