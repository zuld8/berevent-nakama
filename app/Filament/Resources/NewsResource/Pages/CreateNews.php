<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNews extends CreateRecord
{
    protected static string $resource = NewsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $status = $data['status'] ?? 'draft';
        if ($status === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        if ($status === 'draft') {
            $data['published_at'] = null;
        }
        return $data;
    }
}
