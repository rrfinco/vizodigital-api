<?php

namespace App\Filament\Resources\ApiEndpoints\Pages;

use App\Filament\Concerns\HasEndpointPublishActions;
use App\Filament\Resources\ApiEndpoints\ApiEndpointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApiEndpoint extends EditRecord
{
    use HasEndpointPublishActions;

    protected static string $resource = ApiEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getEndpointPublishHeaderActions(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
