<?php

namespace App\Filament\Widgets;

use App\Models\Donation;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Pendapatan 6 Bulan Terakhir';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $months = collect();
        $orderData = collect();
        $donationData = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push($date->translatedFormat('M Y'));

            $orderData->push(
                (float) Order::where('status', 'paid')
                    ->whereYear('paid_at', $date->year)
                    ->whereMonth('paid_at', $date->month)
                    ->sum('total_amount')
            );

            $donationData->push(
                (float) Donation::where('status', 'paid')
                    ->whereYear('paid_at', $date->year)
                    ->whereMonth('paid_at', $date->month)
                    ->sum('amount')
            );
        }

        return [
            'datasets' => [
                [
                    'label' => 'Order (Rp)',
                    'data' => $orderData->toArray(),
                    'backgroundColor' => 'rgba(14, 165, 233, 0.15)',
                    'borderColor' => 'rgb(14, 165, 233)',
                    'pointBackgroundColor' => 'rgb(14, 165, 233)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Donasi (Rp)',
                    'data' => $donationData->toArray(),
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'pointBackgroundColor' => 'rgb(245, 158, 11)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
