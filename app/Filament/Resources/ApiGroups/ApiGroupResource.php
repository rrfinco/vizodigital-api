<?php

namespace App\Filament\Resources\ApiGroups;

use App\Filament\Concerns\HasCmsResourceAuthorization;
use App\Filament\RelationManagers\BaseUrlsRelationManager;
use App\Filament\Resources\ApiGroups\Pages\CreateApiGroup;
use App\Filament\Resources\ApiGroups\Pages\EditApiGroup;
use App\Filament\Resources\ApiGroups\Pages\ListApiGroups;
use App\Filament\Resources\ApiGroups\Schemas\ApiGroupForm;
use App\Filament\Resources\ApiGroups\Tables\ApiGroupsTable;
use App\Models\ApiGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ApiGroupResource extends Resource
{
    use HasCmsResourceAuthorization;

    protected static ?string $model = ApiGroup::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Documentation CMS';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Groups';

    public static function form(Schema $schema): Schema
    {
        return ApiGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiGroupsTable::configure($table);
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
            'index' => ListApiGroups::route('/'),
            'create' => CreateApiGroup::route('/create'),
            'edit' => EditApiGroup::route('/{record}/edit'),
        ];
    }
}
