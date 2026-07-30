<?php

namespace App\Filament\Resources\ApiEndpoints\Schemas;

use App\Enums\HttpMethod;
use App\Filament\Support\PublishStatusField;
use App\Models\ApiCategory;
use App\Models\ApiVersion;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ApiEndpointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Endpoint')
                    ->columns(2)
                    ->schema([
                        Select::make('api_version_id')
                            ->label('API version')
                            ->relationship('version', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->default(fn () => ApiVersion::query()->where('is_default', true)->value('id'))
                            ->afterStateUpdated(fn (callable $set) => $set('api_category_id', null)),
                        Select::make('api_category_id')
                            ->label('Category')
                            ->options(function (callable $get): array {
                                $versionId = $get('api_version_id');

                                if (! $versionId) {
                                    return [];
                                }

                                return ApiCategory::query()
                                    ->where('api_version_id', $versionId)
                                    ->orderBy('sort_order')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->required()
                            ->dehydrated(false),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, ?string $operation): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        Select::make('method')
                            ->options(HttpMethod::class)
                            ->required()
                            ->default(HttpMethod::Get->value),
                        TextInput::make('path')
                            ->required()
                            ->placeholder('/v1/flight/search')
                            ->maxLength(255),
                        TextInput::make('summary')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        MarkdownEditor::make('description_md')
                            ->label('Description')
                            ->columnSpanFull(),
                        PublishStatusField::make(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }
}
