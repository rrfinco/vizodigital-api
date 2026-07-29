<?php

namespace App\Filament\Resources\DocumentationPages\Pages;

use App\Filament\Resources\DocumentationPages\DocumentationPageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentationPage extends EditRecord
{
    protected static string $resource = DocumentationPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->url(fn (): string => route('docs.preview.pages.show', $this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => auth()->user()?->can('docs.preview') ?? false),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
