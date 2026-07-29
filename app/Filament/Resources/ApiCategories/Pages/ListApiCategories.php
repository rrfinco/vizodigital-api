<?php

namespace App\Filament\Resources\ApiCategories\Pages;

use App\Filament\Resources\ApiCategories\ApiCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApiCategories extends ListRecords
{
    protected static string $resource = ApiCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
