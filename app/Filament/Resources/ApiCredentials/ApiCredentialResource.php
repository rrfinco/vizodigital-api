<?php

namespace App\Filament\Resources\ApiCredentials;

use App\Filament\Concerns\HasManagedResourceAuthorization;
use App\Filament\Resources\ApiCredentials\Pages\CreateApiCredential;
use App\Filament\Resources\ApiCredentials\Pages\EditApiCredential;
use App\Filament\Resources\ApiCredentials\Pages\ListApiCredentials;
use App\Filament\Resources\ApiCredentials\Schemas\ApiCredentialForm;
use App\Filament\Resources\ApiCredentials\Tables\ApiCredentialsTable;
use App\Models\ApiCredential;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ApiCredentialResource extends Resource
{
    use HasManagedResourceAuthorization;

    protected static function managePermission(): string
    {
        return 'api-keys.manage';
    }

    protected static ?string $model = ApiCredential::class;

    protected static ?string $recordTitleAttribute = 'client_id';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'API Credentials';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'environment']);
    }

    public static function form(Schema $schema): Schema
    {
        return ApiCredentialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiCredentialsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiCredentials::route('/'),
            'create' => CreateApiCredential::route('/create'),
            'edit' => EditApiCredential::route('/{record}/edit'),
        ];
    }
}
