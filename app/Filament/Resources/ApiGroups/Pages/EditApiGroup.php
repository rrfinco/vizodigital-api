<?php

namespace App\Filament\Resources\ApiGroups\Pages;

use App\Filament\Resources\ApiGroups\ApiGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApiGroup extends EditRecord
{
    protected static string $resource = ApiGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
