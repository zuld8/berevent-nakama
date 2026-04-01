<?php

namespace App\Filament\Resources\CampaignArticleResource\Pages;

use App\Filament\Resources\CampaignArticleResource;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaignArticle extends CreateRecord
{
    protected static string $resource = CampaignArticleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = Auth::id();
        return $data;
    }
}

