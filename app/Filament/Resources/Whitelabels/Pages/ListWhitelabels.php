<?php

namespace App\Filament\Resources\Whitelabels\Pages;

use App\Filament\Resources\Whitelabels\WhitelabelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhitelabels extends ListRecords
{
    protected static string $resource = WhitelabelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
