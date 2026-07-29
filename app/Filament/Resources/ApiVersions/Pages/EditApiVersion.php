<?php

namespace App\Filament\Resources\ApiVersions\Pages;

use App\Filament\Resources\ApiVersions\ApiVersionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApiVersion extends EditRecord
{
    protected static string $resource = ApiVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
