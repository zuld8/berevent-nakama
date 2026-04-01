<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Resources\Pages\EditRecord;

class EditNews extends EditRecord
{
    protected static string $resource = NewsResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newStatus = $data['status'] ?? $this->record->status ?? 'draft';
        $oldStatus = (string) ($this->record->status ?? 'draft');

        if ($newStatus === 'published' && empty($data['published_at'])) {
            // Set publish time if becoming published and none set
            $data['published_at'] = now();
        }
        if ($newStatus === 'draft') {
            // Clear publish time when reverted to draft
            $data['published_at'] = null;
        }
        return $data;
    }
}
