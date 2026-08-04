<?php

namespace App\Filament\Resources\DeveloperWallets\Pages;

use App\Filament\Resources\DeveloperWallets\DeveloperWalletResource;
use Filament\Resources\Pages\ListRecords;

class ListDeveloperWallets extends ListRecords
{
    protected static string $resource = DeveloperWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
