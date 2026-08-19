<?php

namespace App\Filament\Resources\TaxationOrders\Pages;

use App\Filament\Resources\TaxationOrders\TaxationOrderResource;
use App\Models\TaxationOrder;
use App\Services\Taxation\TaxationApiService;
use Filament\Resources\Pages\EditRecord;

class EditTaxationOrder extends EditRecord
{
    protected static string $resource = TaxationOrderResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var TaxationOrder $order */
        $order = $this->record;
        $status = (string) ($data['status'] ?? $order->status);

        if ($status !== $order->status) {
            app(TaxationApiService::class)->markStatus($order, $status);
        }

        return [
            'status' => $order->fresh()?->status ?? $status,
        ];
    }
}
