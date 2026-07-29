<?php

namespace App\Filament\Resources\ApiEndpoints\Schemas;

use App\Enums\HttpMethod;
use App\Filament\Support\PublishStatusField;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use Filament\Forms\Components\DateTimePicker;
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
                            ->default(fn () => ApiVersion::query()->where('is_default', true)->value('id')),
                        Select::make('api_group_id')
                            ->label('Group')
                            ->options(function (callable $get): array {
                                $versionId = $get('api_version_id');

                                return ApiGroup::query()
                                    ->when(
                                        $versionId,
                                        fn ($q) => $q->whereHas('category', fn ($cq) => $cq->where('api_version_id', $versionId))
                                    )
                                    ->with('category')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (ApiGroup $group) => [
                                        $group->id => ($group->category?->name ? $group->category->name.' / ' : '').$group->name,
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->required(),
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
                            ->placeholder('/v1/resource')
                            ->maxLength(255),
                        TextInput::make('summary')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        MarkdownEditor::make('description_md')
                            ->label('Description')
                            ->columnSpanFull(),
                        PublishStatusField::make(),
                        TextInput::make('permission_name')
                            ->placeholder('e.g. payments.create'),
                        TextInput::make('rate_limit')
                            ->placeholder('e.g. 60/minute'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        DateTimePicker::make('published_at'),
                    ]),
            ]);
    }
}
