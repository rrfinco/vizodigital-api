<?php

namespace App\Filament\Resources\TaxationClients;

use App\Filament\Resources\TaxationClients\Pages\ListTaxationClients;
use App\Filament\Resources\TaxationClients\Pages\ViewTaxationClient;
use App\Filament\Resources\TaxationClients\Tables\TaxationClientsTable;
use App\Models\TaxationClient;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TaxationClientResource extends Resource
{
    protected static ?string $model = TaxationClient::class;

    protected static ?string $slug = 'taxation-clients';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Taxation';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Clients';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'whitelabel']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('users.manage') ?? false;
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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Client')
                ->columns(2)
                ->schema([
                    TextEntry::make('id')->label('Client ID'),
                    TextEntry::make('client_request_id')->placeholder('—'),
                    TextEntry::make('first_name'),
                    TextEntry::make('middle_name'),
                    TextEntry::make('last_name'),
                    TextEntry::make('email'),
                    TextEntry::make('phone'),
                    TextEntry::make('pan'),
                    TextEntry::make('aadhaar'),
                    TextEntry::make('created_at')->dateTime(),
                ]),
            Section::make('Residence')
                ->columns(2)
                ->schema([
                    TextEntry::make('residence_address')->columnSpanFull(),
                    TextEntry::make('residence_city'),
                    TextEntry::make('residence_pincode'),
                    TextEntry::make('residence_state'),
                ]),
            Section::make('Office')
                ->columns(2)
                ->schema([
                    TextEntry::make('office_address')->columnSpanFull(),
                    TextEntry::make('office_city'),
                    TextEntry::make('office_pincode'),
                    TextEntry::make('office_state'),
                ]),
            Section::make('Developer')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.name'),
                    TextEntry::make('user.email'),
                    TextEntry::make('whitelabel.name')->label('White-label')->placeholder('B2C'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return TaxationClientsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxationClients::route('/'),
            'view' => ViewTaxationClient::route('/{record}'),
        ];
    }
}
