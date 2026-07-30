<?php

namespace App\Filament\Resources\ApiEndpoints\Pages;

use App\Filament\Concerns\HasEndpointPublishActions;
use App\Filament\Concerns\ResolvesEndpointCategoryGroup;
use App\Filament\Resources\ApiEndpoints\ApiEndpointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApiEndpoint extends EditRecord
{
    use HasEndpointPublishActions;
    use ResolvesEndpointCategoryGroup;

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
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['api_category_id'] = $this->record?->group?->api_category_id;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->resolveGroupFromCategory($data);
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
