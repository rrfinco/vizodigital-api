<?php

namespace App\Filament\Concerns;

use App\Actions\Documentation\PublishEndpoint;
use App\Actions\Documentation\UnpublishEndpoint;
use App\Enums\PublishStatus;
use App\Models\ApiEndpoint;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

trait HasEndpointPublishActions
{
    /**
     * @return array<int, Action>
     */
    protected function getEndpointPublishHeaderActions(): array
    {
        /** @var ApiEndpoint $record */
        $record = $this->getRecord();

        return [
            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->url(fn (): string => route('docs.preview.endpoints.show', $record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => auth()->user()?->can('docs.preview') ?? false),
            Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Publish endpoint')
                ->modalDescription('Published endpoints become visible on the public docs.')
                ->visible(fn (): bool => (auth()->user()?->can('docs.publish') ?? false)
                    && $record->status !== PublishStatus::Published)
                ->action(function () use ($record): void {
                    app(PublishEndpoint::class)($record);

                    Notification::make()
                        ->title('Endpoint published')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'published_at']);
                }),
            Action::make('unpublish')
                ->label('Unpublish')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Unpublish endpoint')
                ->modalDescription('The endpoint will return to draft and disappear from public docs.')
                ->visible(fn (): bool => (auth()->user()?->can('docs.publish') ?? false)
                    && $record->isPubliclyVisible())
                ->action(function () use ($record): void {
                    app(UnpublishEndpoint::class)($record);

                    Notification::make()
                        ->title('Endpoint unpublished')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'published_at']);
                }),
        ];
    }
}
