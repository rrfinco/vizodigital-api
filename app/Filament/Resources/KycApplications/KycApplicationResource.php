<?php

namespace App\Filament\Resources\KycApplications;

use App\Enums\OnboardingStatus;
use App\Filament\Concerns\HasManagedResourceAuthorization;
use App\Filament\Resources\KycApplications\Pages\ListKycApplications;
use App\Filament\Resources\KycApplications\Pages\ViewKycApplication;
use App\Filament\Resources\KycApplications\Tables\KycApplicationsTable;
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
    use HasManagedResourceAuthorization;

    protected static function managePermission(): string
    {
        return 'kyc.manage';
    }

    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'kyc-applications';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'KYC Applications';

    protected static ?string $modelLabel = 'KYC application';

    protected static ?string $pluralModelLabel = 'KYC applications';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->role('developer')
            ->whereIn('onboarding_status', [
                OnboardingStatus::PendingKyc->value,
                OnboardingStatus::KycSubmitted->value,
                OnboardingStatus::Rejected->value,
                OnboardingStatus::Approved->value,
            ])
            ->withCount('kycDocuments')
            ->with(['kycDocuments', 'approvedBy']);
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

    public static function canCreate(): bool
    {
        return false;
    }
}
