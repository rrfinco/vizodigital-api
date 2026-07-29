<?php

namespace App\Filament\Resources\ApiCategories;

use App\Filament\Concerns\HasCmsResourceAuthorization;
use App\Filament\RelationManagers\BaseUrlsRelationManager;
use App\Filament\Resources\ApiCategories\Pages\CreateApiCategory;
use App\Filament\Resources\ApiCategories\Pages\EditApiCategory;
use App\Filament\Resources\ApiCategories\Pages\ListApiCategories;
use App\Filament\Resources\ApiCategories\Schemas\ApiCategoryForm;
use App\Filament\Resources\ApiCategories\Tables\ApiCategoriesTable;
use App\Models\ApiCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ApiCategoryResource extends Resource
{
    use HasCmsResourceAuthorization;

    protected static ?string $model = ApiCategory::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Documentation CMS';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Categories';

    public static function form(Schema $schema): Schema
    {
        return ApiCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiCategoriesTable::configure($table);
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
            'index' => ListApiCategories::route('/'),
            'create' => CreateApiCategory::route('/create'),
            'edit' => EditApiCategory::route('/{record}/edit'),
        ];
    }
}
