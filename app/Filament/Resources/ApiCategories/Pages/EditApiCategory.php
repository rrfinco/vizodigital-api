<?php

namespace App\Filament\Resources\ApiCategories\Pages;

use App\Filament\Resources\ApiCategories\ApiCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApiCategory extends EditRecord
{
    protected static string $resource = ApiCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
