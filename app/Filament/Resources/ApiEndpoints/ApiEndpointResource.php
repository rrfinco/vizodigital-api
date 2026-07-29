<?php

namespace App\Filament\Resources\ApiEndpoints;

use App\Filament\Concerns\HasCmsResourceAuthorization;
use App\Filament\RelationManagers\BaseUrlsRelationManager;
use App\Filament\Resources\ApiEndpoints\Pages\CreateApiEndpoint;
use App\Filament\Resources\ApiEndpoints\Pages\EditApiEndpoint;
use App\Filament\Resources\ApiEndpoints\Pages\ListApiEndpoints;
use App\Filament\Resources\ApiEndpoints\RelationManagers\CodeSamplesRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\ErrorsRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\ExamplesRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\HeadersRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\NotesRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\ParametersRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\RequestBodiesRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\SectionsRelationManager;
use App\Filament\Resources\ApiEndpoints\Schemas\ApiEndpointForm;
use App\Filament\Resources\ApiEndpoints\Tables\ApiEndpointsTable;
use App\Models\ApiEndpoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ApiEndpointResource extends Resource
{
    use HasCmsResourceAuthorization;

    protected static ?string $model = ApiEndpoint::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    protected static string|UnitEnum|null $navigationGroup = 'Documentation CMS';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Endpoints';

    public static function form(Schema $schema): Schema
    {
        return ApiEndpointForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiEndpointsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
            HeadersRelationManager::class,
            ParametersRelationManager::class,
            RequestBodiesRelationManager::class,
            ResponsesRelationManager::class,
            ErrorsRelationManager::class,
            NotesRelationManager::class,
            ExamplesRelationManager::class,
            CodeSamplesRelationManager::class,
            BaseUrlsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiEndpoints::route('/'),
            'create' => CreateApiEndpoint::route('/create'),
            'edit' => EditApiEndpoint::route('/{record}/edit'),
        ];
    }
}
