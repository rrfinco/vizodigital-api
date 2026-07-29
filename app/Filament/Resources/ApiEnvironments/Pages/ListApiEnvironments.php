<?php

namespace App\Filament\Resources\ApiEnvironments\Pages;

use App\Filament\Resources\ApiEnvironments\ApiEnvironmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApiEnvironments extends ListRecords
{
    protected static string $resource = ApiEnvironmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
