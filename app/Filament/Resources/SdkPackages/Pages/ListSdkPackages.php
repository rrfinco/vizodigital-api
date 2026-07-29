<?php

namespace App\Filament\Resources\SdkPackages\Pages;

use App\Filament\Resources\SdkPackages\SdkPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSdkPackages extends ListRecords
{
    protected static string $resource = SdkPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
