<?php

namespace App\Filament\Resources\SdkPackages;

use App\Filament\Concerns\HasCmsResourceAuthorization;
use App\Filament\Resources\SdkPackages\Pages\CreateSdkPackage;
use App\Filament\Resources\SdkPackages\Pages\EditSdkPackage;
use App\Filament\Resources\SdkPackages\Pages\ListSdkPackages;
use App\Filament\Resources\SdkPackages\Schemas\SdkPackageForm;
use App\Filament\Resources\SdkPackages\Tables\SdkPackagesTable;
use App\Models\SdkPackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SdkPackageResource extends Resource
{
    use HasCmsResourceAuthorization;

    protected static ?string $model = SdkPackage::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Documentation CMS';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'SDK packages';

    public static function form(Schema $schema): Schema
    {
        return SdkPackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SdkPackagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSdkPackages::route('/'),
            'create' => CreateSdkPackage::route('/create'),
            'edit' => EditSdkPackage::route('/{record}/edit'),
        ];
    }
}
