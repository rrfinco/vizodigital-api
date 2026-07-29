<?php

namespace App\Filament\Resources\ApiEndpoints\Pages;

use App\Filament\Resources\ApiEndpoints\ApiEndpointResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiEndpoint extends CreateRecord
{
    protected static string $resource = ApiEndpointResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
