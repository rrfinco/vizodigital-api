<?php

namespace App\Filament\Resources\Deposits;

use App\Filament\Concerns\HasManagedResourceAuthorization;
use App\Filament\Resources\Deposits\Pages\ListDeposits;
use App\Filament\Resources\Deposits\Pages\ViewDeposit;
use App\Filament\Resources\Deposits\Tables\DepositsTable;
use App\Models\Deposit;
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
use UnitEnum;

class DepositResource extends Resource
{
    use HasManagedResourceAuthorization;

    protected static function managePermission(): string
    {
        return 'deposits.manage';
    }

    protected static ?string $model = Deposit::class;

    protected static ?string $recordTitleAttribute = 'order_id';

    protected static ?string $slug = 'deposits';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 16;

    protected static ?string $navigationLabel = 'Wallet Deposits';

    protected static ?string $modelLabel = 'deposit';

    protected static ?string $pluralModelLabel = 'deposits';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->where('method', Deposit::METHOD_BANK_TRANSFER)
            ->where('status', 'pending')
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
            ->with(['user', 'reviewedBy']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(static::managePermission()) ?? false;
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
                Section::make('Deposit')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('order_id')->label('Order ID'),
                        TextEntry::make('method')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                Deposit::METHOD_BANK_TRANSFER => 'Bank transfer',
                                default => 'Online',
                            })
                            ->color(fn (string $state): string => $state === Deposit::METHOD_BANK_TRANSFER ? 'warning' : 'info'),
                        TextEntry::make('amount')
                            ->money('INR'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'success' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger',
                                default => 'danger',
                            })
                            ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                        TextEntry::make('utr')->label('UTR / Ref')->placeholder('—'),
                        TextEntry::make('gateway_ref')->label('Gateway ref')->placeholder('—'),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('admin_notes')->columnSpanFull()->placeholder('—'),
                    ]),
                Section::make('User')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name'),
                        TextEntry::make('user.email'),
                        TextEntry::make('user.company_name')->placeholder('—'),
                        TextEntry::make('user.phone')->placeholder('—'),
                        TextEntry::make('reviewedBy.name')->label('Reviewed by')->placeholder('—'),
                    ]),
                Section::make('Payment proof')
                    ->visible(fn (Deposit $record): bool => filled($record->proof_path))
                    ->schema([
                        ImageEntry::make('proof_path')
                            ->label('Screenshot')
                            ->disk('public')
                            ->visible(fn (Deposit $record): bool => filled($record->proof_path)
                                && preg_match('/\.(jpe?g|png|gif|webp)$/i', (string) $record->proof_path) === 1),
                        TextEntry::make('proof_path')
                            ->label('Proof file')
                            ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : '—')
                            ->url(fn (Deposit $record): ?string => $record->proof_path
                                ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->proof_path)
                                : null)
                            ->openUrlInNewTab(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return DepositsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeposits::route('/'),
            'view' => ViewDeposit::route('/{record}'),
        ];
    }
}
