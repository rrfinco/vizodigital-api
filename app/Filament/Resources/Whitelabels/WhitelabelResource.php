<?php

namespace App\Filament\Resources\Whitelabels;

use App\Filament\Resources\Whitelabels\Pages\CreateWhitelabel;
use App\Filament\Resources\Whitelabels\Pages\EditWhitelabel;
use App\Filament\Resources\Whitelabels\Pages\ListWhitelabels;
use App\Filament\Resources\Whitelabels\RelationManagers\DomainsRelationManager;
use App\Filament\Resources\Whitelabels\RelationManagers\WalletTransactionsRelationManager;
use App\Filament\Resources\Whitelabels\Schemas\WhitelabelForm;
use App\Filament\Resources\Whitelabels\Tables\WhitelabelsTable;
use App\Models\Whitelabel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class WhitelabelResource extends Resource
{
    protected static ?string $model = Whitelabel::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'whitelabels';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'White-label';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'White-labels';

    protected static ?string $modelLabel = 'white-label';

    protected static ?string $pluralModelLabel = 'white-labels';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['owner', 'domains']);
    }

    public static function form(Schema $schema): Schema
    {
        return WhitelabelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhitelabelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DomainsRelationManager::class,
            WalletTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhitelabels::route('/'),
            'create' => CreateWhitelabel::route('/create'),
            'edit' => EditWhitelabel::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('whitelabels.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }
}
