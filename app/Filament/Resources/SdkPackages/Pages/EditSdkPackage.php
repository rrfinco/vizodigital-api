<?php

namespace App\Filament\Resources\SdkPackages\Pages;

use App\Filament\Resources\SdkPackages\SdkPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSdkPackage extends EditRecord
{
    protected static string $resource = SdkPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
