<?php

namespace App\Filament\Resources\WhitelabelFloatRequests;

use App\Filament\Resources\WhitelabelFloatRequests\Pages\ListWhitelabelFloatRequests;
use App\Filament\Resources\WhitelabelFloatRequests\Pages\ViewWhitelabelFloatRequest;
use App\Filament\Resources\WhitelabelFloatRequests\Tables\WhitelabelFloatRequestsTable;
use App\Models\WhitelabelFloatRequest;
use BackedEnum;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class WhitelabelFloatRequestResource extends Resource
{
    protected static ?string $model = WhitelabelFloatRequest::class;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $slug = 'wallet-top-up-requests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'White-label';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Wallet top-up requests';

    protected static ?string $modelLabel = 'wallet top-up request';

    protected static ?string $pluralModelLabel = 'wallet top-up requests';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->where('status', WhitelabelFloatRequest::STATUS_PENDING)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['whitelabel', 'requester', 'reviewedBy']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('whitelabel-float.manage') ?? false;
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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('whitelabel.name')->label('White-label'),
                        TextEntry::make('amount')->money('INR'),
                        TextEntry::make('method')->badge(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'approved' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                        TextEntry::make('utr')->label('UTR')->placeholder('—'),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('admin_notes')->columnSpanFull()->placeholder('—'),
                    ]),
                Section::make('Requester')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('requester.name'),
                        TextEntry::make('requester.email'),
                        TextEntry::make('reviewedBy.name')->label('Reviewed by')->placeholder('—'),
                    ]),
                Section::make('Payment proof')
                    ->visible(fn (WhitelabelFloatRequest $record): bool => filled($record->proof_path))
                    ->schema([
                        ImageEntry::make('proof_path')
                            ->label('Screenshot')
                            ->disk('public')
                            ->visible(fn (WhitelabelFloatRequest $record): bool => filled($record->proof_path)
                                && preg_match('/\.(jpe?g|png|gif|webp)$/i', (string) $record->proof_path) === 1),
                        TextEntry::make('proof_path')
                            ->label('Proof file')
                            ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : '—')
                            ->url(fn (WhitelabelFloatRequest $record): ?string => $record->proof_path
                                ? Storage::disk('public')->url($record->proof_path)
                                : null)
                            ->openUrlInNewTab(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return WhitelabelFloatRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhitelabelFloatRequests::route('/'),
            'view' => ViewWhitelabelFloatRequest::route('/{record}'),
        ];
    }
}
