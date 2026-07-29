<?php

namespace App\Filament\Resources\ApiEnvironments\Pages;

use App\Filament\Resources\ApiEnvironments\ApiEnvironmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApiEnvironment extends EditRecord
{
    protected static string $resource = ApiEnvironmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
