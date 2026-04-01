<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newStatus = $data['status'] ?? $this->record->status ?? 'draft';
        $oldStatus = (string) ($this->record->status ?? 'draft');

        if ($newStatus === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        if ($newStatus === 'draft') {
            $data['published_at'] = null;
        }
        return $data;
    }
}
