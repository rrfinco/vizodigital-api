<?php

namespace App\Filament\Resources\PostmanCollections\Pages;

use App\Filament\Resources\PostmanCollections\PostmanCollectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPostmanCollection extends EditRecord
{
    protected static string $resource = PostmanCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
