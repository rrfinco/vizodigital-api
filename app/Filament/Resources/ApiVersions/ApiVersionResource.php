<?php

namespace App\Filament\Resources\ApiVersions;

use App\Filament\Concerns\HasManagedResourceAuthorization;
use App\Filament\RelationManagers\BaseUrlsRelationManager;
use App\Filament\Resources\ApiVersions\Pages\CreateApiVersion;
use App\Filament\Resources\ApiVersions\Pages\EditApiVersion;
use App\Filament\Resources\ApiVersions\Pages\ListApiVersions;
use App\Filament\Resources\ApiVersions\Schemas\ApiVersionForm;
use App\Filament\Resources\ApiVersions\Tables\ApiVersionsTable;
use App\Models\ApiVersion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ApiVersionResource extends Resource
{
    use HasManagedResourceAuthorization;

    protected static function managePermission(): string
    {
        return 'versions.manage';
    }

    protected static ?string $model = ApiVersion::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Documentation CMS';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Versions';

    public static function form(Schema $schema): Schema
    {
        return ApiVersionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiVersionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BaseUrlsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiVersions::route('/'),
            'create' => CreateApiVersion::route('/create'),
            'edit' => EditApiVersion::route('/{record}/edit'),
        ];
    }
}
