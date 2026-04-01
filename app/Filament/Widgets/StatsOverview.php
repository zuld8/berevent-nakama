<?php

namespace App\Filament\Widgets;

use App\Models\Donation;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalEvents = Event::count();
        $publishedEvents = Event::where('status', 'published')->count();
        $totalOrders = Order::count();
        $paidOrders = Order::where('status', 'paid')->count();
        $totalDonations = Donation::count();
        $paidDonations = Donation::where('status', 'paid')->count();
        $totalUsers = User::count();

        $orderRevenue = Order::where('status', 'paid')->sum('total_amount');
        $donationRevenue = Donation::where('status', 'paid')->sum('amount');

        return [
            Stat::make('Total Event', $totalEvents)
                ->description($publishedEvents . ' published')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary')
                ->chart(self::getLast7DaysCounts(Event::class)),

            Stat::make('Total Order', $totalOrders)
                ->description($paidOrders . ' terbayar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart(self::getLast7DaysCounts(Order::class)),

            Stat::make('Total Donasi', $totalDonations)
                ->description($paidDonations . ' terbayar')
                ->descriptionIcon('heroicon-m-heart')
                ->color('warning')
                ->chart(self::getLast7DaysCounts(Donation::class)),

            Stat::make('Pendapatan', 'Rp ' . number_format($orderRevenue + $donationRevenue, 0, ',', '.'))
                ->description('Order: Rp ' . number_format($orderRevenue, 0, ',', '.') . ' | Donasi: Rp ' . number_format($donationRevenue, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Total User', $totalUsers)
                ->description('Terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->chart(self::getLast7DaysCounts(User::class)),
        ];
    }

    protected static function getLast7DaysCounts(string $model): array
    {
        $counts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $counts[] = $model::whereDate('created_at', $date)->count();
        }
        return $counts;
    }
}
