<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

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
