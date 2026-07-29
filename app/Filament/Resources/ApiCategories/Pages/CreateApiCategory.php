<?php

namespace App\Filament\Resources\ApiCategories\Pages;

use App\Filament\Resources\ApiCategories\ApiCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiCategory extends CreateRecord
{
    protected static string $resource = ApiCategoryResource::class;
}
