<?php

namespace App\Filament\Resources\TaxationServices\Pages;

use App\Filament\Resources\TaxationServices\TaxationServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxationServices extends ListRecords
{
    protected static string $resource = TaxationServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
