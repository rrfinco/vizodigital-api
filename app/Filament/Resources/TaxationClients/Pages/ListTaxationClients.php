<?php

namespace App\Filament\Resources\TaxationClients\Pages;

use App\Filament\Resources\TaxationClients\TaxationClientResource;
use Filament\Resources\Pages\ListRecords;

class ListTaxationClients extends ListRecords
{
    protected static string $resource = TaxationClientResource::class;
}
