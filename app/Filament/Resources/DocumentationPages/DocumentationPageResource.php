<?php

namespace App\Filament\Resources\DocumentationPages;

use App\Filament\Concerns\HasCmsResourceAuthorization;
use App\Filament\Resources\DocumentationPages\Pages\CreateDocumentationPage;
use App\Filament\Resources\DocumentationPages\Pages\EditDocumentationPage;
use App\Filament\Resources\DocumentationPages\Pages\ListDocumentationPages;
use App\Filament\Resources\DocumentationPages\RelationManagers\PageSectionsRelationManager;
use App\Filament\Resources\DocumentationPages\Schemas\DocumentationPageForm;
use App\Filament\Resources\DocumentationPages\Tables\DocumentationPagesTable;
use App\Models\DocumentationPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DocumentationPageResource extends Resource
{
    use HasCmsResourceAuthorization;

    protected static ?string $model = DocumentationPage::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Documentation CMS';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Pages';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return DocumentationPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentationPagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PageSectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentationPages::route('/'),
            'create' => CreateDocumentationPage::route('/create'),
            'edit' => EditDocumentationPage::route('/{record}/edit'),
        ];
    }
}
