<?php

namespace App\Filament\Resources\TaxationOrders;

use App\Filament\Resources\TaxationOrders\Pages\EditTaxationOrder;
use App\Filament\Resources\TaxationOrders\Pages\ListTaxationOrders;
use App\Filament\Resources\TaxationOrders\Pages\ViewTaxationOrder;
use App\Filament\Resources\TaxationOrders\Schemas\TaxationOrderForm;
use App\Filament\Resources\TaxationOrders\Tables\TaxationOrdersTable;
use App\Models\TaxationDocument;
use App\Models\TaxationOrder;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TaxationOrderResource extends Resource
{
    protected static ?string $model = TaxationOrder::class;

    protected static ?string $slug = 'taxation-orders';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Taxation';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Orders';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'whitelabel', 'client', 'documents', 'documentsReviewer']);
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
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return TaxationOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')
                ->columns(2)
                ->schema([
                    TextEntry::make('id')->label('Order ID'),
                    TextEntry::make('api_request_id'),
                    TextEntry::make('client_request_id')->placeholder('—'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('documents_status')
                        ->label('Documents')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            TaxationOrder::DOCUMENTS_APPROVED => 'success',
                            TaxationOrder::DOCUMENTS_VERIFIED => 'info',
                            TaxationOrder::DOCUMENTS_SUBMITTED => 'warning',
                            TaxationOrder::DOCUMENTS_REJECTED => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('taxation_service_id')->label('Service ID'),
                    TextEntry::make('service_name'),
                    TextEntry::make('amount')->money('INR'),
                    TextEntry::make('created_at')->dateTime(),
                ]),
            Section::make('Client')
                ->columns(2)
                ->schema([
                    TextEntry::make('taxation_client_id')->label('Client ID'),
                    TextEntry::make('client.pan')->label('PAN'),
                    TextEntry::make('client.phone')->label('Phone'),
                    TextEntry::make('client.email')->label('Email'),
                ]),
            Section::make('Developer')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.name'),
                    TextEntry::make('user.email'),
                    TextEntry::make('whitelabel.name')->placeholder('B2C'),
                ]),
            Section::make('Documents')
                ->schema([
                    TextEntry::make('documents_note')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('documentsReviewer.name')->label('Reviewed by')->placeholder('—'),
                    TextEntry::make('documents_reviewed_at')->dateTime()->placeholder('—'),
                    RepeatableEntry::make('documents')
                        ->label('')
                        ->schema([
                            TextEntry::make('document_type')
                                ->formatStateUsing(fn (?string $state): string => TaxationDocument::TYPES[$state] ?? (string) $state)
                                ->badge(),
                            TextEntry::make('original_name')->label('File'),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('rejection_reason')->placeholder('—'),
                            TextEntry::make('created_at')->dateTime()->label('Uploaded'),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return TaxationOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxationOrders::route('/'),
            'view' => ViewTaxationOrder::route('/{record}'),
            'edit' => EditTaxationOrder::route('/{record}/edit'),
        ];
    }
}
