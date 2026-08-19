<?php

namespace App\Filament\Resources\TaxationOrders\Pages;

use App\Filament\Resources\TaxationOrders\TaxationOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListTaxationOrders extends ListRecords
{
    protected static string $resource = TaxationOrderResource::class;
}
