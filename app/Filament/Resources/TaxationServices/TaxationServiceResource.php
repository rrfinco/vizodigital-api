<?php

namespace App\Filament\Resources\TaxationServices;

use App\Filament\Resources\TaxationServices\Pages\CreateTaxationService;
use App\Filament\Resources\TaxationServices\Pages\EditTaxationService;
use App\Filament\Resources\TaxationServices\Pages\ListTaxationServices;
use App\Filament\Resources\TaxationServices\Schemas\TaxationServiceForm;
use App\Filament\Resources\TaxationServices\Tables\TaxationServicesTable;
use App\Models\TaxationService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TaxationServiceResource extends Resource
{
    protected static ?string $model = TaxationService::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'taxation-services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Taxation';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $modelLabel = 'taxation service';

    protected static ?string $pluralModelLabel = 'taxation services';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('category');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('users.manage') ?? false;
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
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return TaxationServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxationServicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxationServices::route('/'),
            'create' => CreateTaxationService::route('/create'),
            'edit' => EditTaxationService::route('/{record}/edit'),
        ];
    }
}
