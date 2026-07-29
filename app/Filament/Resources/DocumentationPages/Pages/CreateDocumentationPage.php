<?php

namespace App\Filament\Resources\DocumentationPages\Pages;

use App\Filament\Resources\DocumentationPages\DocumentationPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentationPage extends CreateRecord
{
    protected static string $resource = DocumentationPageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
