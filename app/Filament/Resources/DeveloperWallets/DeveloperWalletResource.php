<?php

namespace App\Filament\Resources\DeveloperWallets;

use App\Enums\Role;
use App\Filament\Resources\DeveloperWallets\Pages\ListDeveloperWallets;
use App\Filament\Resources\DeveloperWallets\Tables\DeveloperWalletsTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DeveloperWalletResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'developer-wallets';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyRupee;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 17;

    protected static ?string $navigationLabel = 'Developer wallets';

    protected static ?string $modelLabel = 'developer wallet';

    protected static ?string $pluralModelLabel = 'developer wallets';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('users.manage') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->role(Role::Developer->value);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return DeveloperWalletsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeveloperWallets::route('/'),
        ];
    }
}
