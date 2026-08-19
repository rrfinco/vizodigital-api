<?php

namespace App\Filament\Resources\TaxationOrders\Schemas;

use App\Models\TaxationOrder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaxationOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Status')
                ->schema([
                    TextInput::make('api_request_id')->disabled(),
                    TextInput::make('service_name')->disabled(),
                    TextInput::make('amount')->disabled()->prefix('₹'),
                    Select::make('status')
                        ->options([
                            TaxationOrder::STATUS_PENDING => 'Pending',
                            TaxationOrder::STATUS_PROCESSING => 'Processing',
                            TaxationOrder::STATUS_COMPLETED => 'Completed',
                            TaxationOrder::STATUS_CANCELLED => 'Cancelled (refund)',
                        ])
                        ->required(),
                ]),
        ]);
    }
}
