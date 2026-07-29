<?php

namespace App\Filament\Resources\ApiGroups\Pages;

use App\Filament\Resources\ApiGroups\ApiGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiGroup extends CreateRecord
{
    protected static string $resource = ApiGroupResource::class;
}
