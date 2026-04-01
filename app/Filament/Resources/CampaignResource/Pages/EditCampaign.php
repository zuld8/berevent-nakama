<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use Filament\Resources\Pages\EditRecord;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Keep organization fixed in single-org scenario
        unset($data['organization_id']);
        return $data;
    }
}

