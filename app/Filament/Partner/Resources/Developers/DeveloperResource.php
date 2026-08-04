<?php

namespace App\Filament\Partner\Resources\Developers;

use App\Enums\Role;
use App\Filament\Partner\Resources\Developers\Pages\ListDevelopers;
use App\Filament\Partner\Resources\Developers\Tables\DevelopersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DeveloperResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'developers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Developers';

    protected static ?string $modelLabel = 'developer';

    protected static ?string $pluralModelLabel = 'developers';

    public static function getEloquentQuery(): Builder
    {
        $wlId = auth()->user()?->whitelabel_id;

        return parent::getEloquentQuery()
            ->role(Role::Developer->value)
            ->where('whitelabel_id', $wlId ?: 0)
            ->orderBy('name');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isWhitelabelPartner() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return DevelopersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevelopers::route('/'),
        ];
    }
}
