<?php

namespace App\Filament\Resources\ApiEndpoints\Pages;

use App\Filament\Resources\ApiEndpoints\ApiEndpointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApiEndpoints extends ListRecords
{
    protected static string $resource = ApiEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
