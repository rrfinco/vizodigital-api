<?php

namespace App\Filament\Resources\ApiVersions\Pages;

use App\Filament\Resources\ApiVersions\ApiVersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApiVersions extends ListRecords
{
    protected static string $resource = ApiVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
