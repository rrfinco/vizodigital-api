<?php

namespace App\Filament\Resources\PostmanCollections\Pages;

use App\Filament\Resources\PostmanCollections\PostmanCollectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostmanCollections extends ListRecords
{
    protected static string $resource = PostmanCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
