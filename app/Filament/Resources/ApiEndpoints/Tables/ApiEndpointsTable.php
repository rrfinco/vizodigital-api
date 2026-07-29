<?php

namespace App\Filament\Resources\ApiEndpoints\Tables;

use App\Actions\Documentation\PublishEndpoint;
use App\Actions\Documentation\UnpublishEndpoint;
use App\Enums\PublishStatus;
use App\Models\ApiEndpoint;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ApiEndpointsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('method')
                    ->badge()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('path')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('group.name')
                    ->label('Group')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('version.name')
                    ->label('Version')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(function (mixed $state): string {
                        $status = $state instanceof PublishStatus
                            ? $state
                            : PublishStatus::tryFrom((string) $state);

                        return match ($status) {
                            PublishStatus::Published => 'success',
                            PublishStatus::Deprecated => 'warning',
                            PublishStatus::Archived => 'gray',
                            default => 'gray',
                        };
                    }),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('api_version_id')
                    ->label('Version')
                    ->relationship('version', 'name'),
                SelectFilter::make('method'),
                SelectFilter::make('status'),
            ])
            ->recordActions([
                Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ApiEndpoint $record): string => route('docs.preview.endpoints.show', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (): bool => auth()->user()?->can('docs.preview') ?? false),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publishSelected')
                        ->label('Publish selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => auth()->user()?->can('docs.publish') ?? false)
                        ->action(function (Collection $records): void {
                            $publisher = app(PublishEndpoint::class);

                            $records->each(function (ApiEndpoint $endpoint) use ($publisher): void {
                                $publisher($endpoint);
                            });

                            Notification::make()
                                ->title('Selected endpoints published')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('unpublishSelected')
                        ->label('Unpublish selected')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => auth()->user()?->can('docs.publish') ?? false)
                        ->action(function (Collection $records): void {
                            $unpublisher = app(UnpublishEndpoint::class);

                            $records->each(function (ApiEndpoint $endpoint) use ($unpublisher): void {
                                $unpublisher($endpoint);
                            });

                            Notification::make()
                                ->title('Selected endpoints unpublished')
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
