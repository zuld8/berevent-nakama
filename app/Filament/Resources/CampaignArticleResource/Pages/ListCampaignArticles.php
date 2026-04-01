<?php

namespace App\Filament\Resources\CampaignArticleResource\Pages;

use App\Filament\Resources\CampaignArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCampaignArticles extends ListRecords
{
    protected static string $resource = CampaignArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

