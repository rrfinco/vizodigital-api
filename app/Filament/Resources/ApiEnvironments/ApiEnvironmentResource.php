<?php

namespace App\Filament\Resources\ApiEnvironments;

use App\Filament\Concerns\HasManagedResourceAuthorization;
use App\Filament\Resources\ApiEnvironments\Pages\CreateApiEnvironment;
use App\Filament\Resources\ApiEnvironments\Pages\EditApiEnvironment;
use App\Filament\Resources\ApiEnvironments\Pages\ListApiEnvironments;
use App\Filament\Resources\ApiEnvironments\Schemas\ApiEnvironmentForm;
use App\Filament\Resources\ApiEnvironments\Tables\ApiEnvironmentsTable;
use App\Models\ApiEnvironment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ApiEnvironmentResource extends Resource
{
    use HasManagedResourceAuthorization;

    protected static function managePermission(): string
    {
        return 'environments.manage';
    }

    protected static ?string $model = ApiEnvironment::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Documentation CMS';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Environments';

    public static function form(Schema $schema): Schema
    {
        return ApiEnvironmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiEnvironmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiEnvironments::route('/'),
            'create' => CreateApiEnvironment::route('/create'),
            'edit' => EditApiEnvironment::route('/{record}/edit'),
        ];
    }
}
