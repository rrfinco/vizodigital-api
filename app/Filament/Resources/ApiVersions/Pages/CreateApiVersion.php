<?php

namespace App\Filament\Resources\ApiVersions\Pages;

use App\Filament\Resources\ApiVersions\ApiVersionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiVersion extends CreateRecord
{
    protected static string $resource = ApiVersionResource::class;
}
