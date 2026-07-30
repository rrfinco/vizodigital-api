<?php

namespace App\Filament\Resources\PostmanCollections;

use App\Filament\Concerns\HasCmsResourceAuthorization;
use App\Filament\Resources\PostmanCollections\Pages\CreatePostmanCollection;
use App\Filament\Resources\PostmanCollections\Pages\EditPostmanCollection;
use App\Filament\Resources\PostmanCollections\Pages\ListPostmanCollections;
use App\Filament\Resources\PostmanCollections\Schemas\PostmanCollectionForm;
use App\Filament\Resources\PostmanCollections\Tables\PostmanCollectionsTable;
use App\Models\PostmanCollection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PostmanCollectionResource extends Resource
{
    use HasCmsResourceAuthorization;

    protected static ?string $model = PostmanCollection::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Documentation CMS';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Postman';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return PostmanCollectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostmanCollectionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPostmanCollections::route('/'),
            'create' => CreatePostmanCollection::route('/create'),
            'edit' => EditPostmanCollection::route('/{record}/edit'),
        ];
    }
}
