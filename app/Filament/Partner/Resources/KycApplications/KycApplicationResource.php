<?php

namespace App\Filament\Partner\Resources\KycApplications;

use App\Enums\OnboardingStatus;
use App\Filament\Partner\Resources\KycApplications\Pages\ListKycApplications;
use App\Filament\Partner\Resources\KycApplications\Pages\ViewKycApplication;
use App\Filament\Partner\Resources\KycApplications\Tables\KycApplicationsTable;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class KycApplicationResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'kyc-applications';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'KYC Applications';

    protected static ?string $modelLabel = 'KYC application';

    protected static ?string $pluralModelLabel = 'KYC applications';

    public static function getNavigationBadge(): ?string
    {
        $wlId = auth()->user()?->whitelabel_id;
        if (! $wlId) {
            return null;
        }

        $count = static::getEloquentQuery()
            ->where('onboarding_status', OnboardingStatus::KycSubmitted->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $wlId = auth()->user()?->whitelabel_id;

        return parent::getEloquentQuery()
            ->role('developer')
            ->where('whitelabel_id', $wlId ?: 0)
            ->whereIn('onboarding_status', [
                OnboardingStatus::PendingKyc->value,
                OnboardingStatus::KycSubmitted->value,
                OnboardingStatus::Rejected->value,
                OnboardingStatus::Approved->value,
            ])
            ->withCount('kycDocuments')
            ->with(['kycDocuments', 'approvedBy']);
    }

    public static function canViewAny(): bool
    {
        return (auth()->user()?->can('whitelabel-kyc.manage') ?? false)
            && auth()->user()?->whitelabel_id !== null;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canViewAny()
            && (int) $record->getAttribute('whitelabel_id') === (int) auth()->user()?->whitelabel_id;
    }

    public static function canCreate(): bool
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
                Section::make('Applicant')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('company_name')->placeholder('—'),
                        TextEntry::make('phone')->placeholder('—'),
                        TextEntry::make('onboarding_status')
                            ->badge()
                            ->color(fn ($state) => $state?->color())
                            ->formatStateUsing(fn ($state) => $state?->label()),
                        TextEntry::make('kyc_submitted_at')->dateTime()->placeholder('—'),
                        TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                        TextEntry::make('approvedBy.name')->label('Approved by')->placeholder('—'),
                        TextEntry::make('rejection_reason')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('created_at')->dateTime()->label('Signed up'),
                    ]),
                Section::make('Documents')
                    ->schema([
                        RepeatableEntry::make('kycDocuments')
                            ->label('')
                            ->schema([
                                TextEntry::make('document_type')->badge(),
                                TextEntry::make('original_name')->label('File'),
                                TextEntry::make('mime_type')->placeholder('—'),
                                TextEntry::make('size')
                                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 1).' KB' : '—'),
                                TextEntry::make('created_at')->dateTime()->label('Uploaded'),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return KycApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKycApplications::route('/'),
            'view' => ViewKycApplication::route('/{record}'),
        ];
    }
}
