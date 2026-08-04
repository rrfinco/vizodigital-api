<?php

namespace App\Filament\Partner\Resources\Developers\Pages;

use App\Filament\Partner\Resources\Developers\DeveloperResource;
use Filament\Resources\Pages\ListRecords;

class ListDevelopers extends ListRecords
{
    protected static string $resource = DeveloperResource::class;
}
