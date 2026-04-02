<?php

namespace App\Filament\Resources\DonationResource\Pages;

use App\Filament\Resources\DonationResource;
use App\Models\Donation;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDonations extends ListRecords
{
    protected static string $resource = DonationResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(Donation::count()),
            'paid' => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid'))
                ->badge(Donation::where('status', 'paid')->count())
                ->badgeColor('success'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['initiated', 'pending']))
                ->badge(Donation::whereIn('status', ['initiated', 'pending'])->count())
                ->badgeColor('warning'),
            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(Donation::where('status', 'failed')->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\DonationResource\Widgets\DonationStatsOverview::class,
        ];
    }
}
