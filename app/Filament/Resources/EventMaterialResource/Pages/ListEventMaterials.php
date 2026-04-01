<?php

namespace App\Filament\Resources\EventMaterialResource\Pages;

use App\Filament\Resources\EventMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEventMaterials extends ListRecords
{
    protected static string $resource = EventMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

