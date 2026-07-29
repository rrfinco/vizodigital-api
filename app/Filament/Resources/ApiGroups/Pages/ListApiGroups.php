<?php

namespace App\Filament\Resources\ApiGroups\Pages;

use App\Filament\Resources\ApiGroups\ApiGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApiGroups extends ListRecords
{
    protected static string $resource = ApiGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
