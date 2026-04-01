<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use App\Models\Organization;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure single-organization default
        $data['organization_id'] = $data['organization_id'] ?? Organization::query()->value('id');
        $data['raised_amount'] = $data['raised_amount'] ?? 0;

        return $data;
    }
}

