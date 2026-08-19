<?php

namespace App\Filament\Partner\Pages;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Models\WhitelabelPlanApiAccess;
use App\Services\Taxation\TaxationCatalog;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ManageDeveloperTaxationAccess extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'Commissions';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Developer taxation';

    protected static ?string $title = 'Developer taxation API access';

    protected string $view = 'filament.partner.pages.manage-developer-taxation-access';

    /**
     * @var array<string, array{status: string}>
     */
    public array $rows = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('whitelabel-commissions.manage') ?? false;
    }

    public function mount(): void
    {
        $this->hydrateRows();
    }

    public function isWhitelabelEnabled(): bool
    {
        $wlId = auth()->user()?->whitelabel_id;
        if (! $wlId) {
            return false;
        }

        $access = WhitelabelPlanApiAccess::resolveFor((int) $wlId, TaxationCatalog::SERVICE_ACCESS_KEY);

        return (bool) ($access['status'] ?? false);
    }

    /**
     * @return Collection<int, User>
     */
    public function developers(): Collection
    {
        $wlId = auth()->user()?->whitelabel_id;
        if (! $wlId) {
            return collect();
        }

        return User::query()
            ->role(Role::Developer->value)
            ->where('whitelabel_id', $wlId)
            ->orderBy('name')
            ->get();
    }

    public function hydrateRows(): void
    {
        $existing = UserPlanApiAccess::query()
            ->where('service', TaxationCatalog::SERVICE_ACCESS_KEY)
            ->whereIn('user_id', $this->developers()->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $this->rows = [];
        foreach ($this->developers() as $user) {
            $found = $existing->get($user->id);
            $this->rows[(string) $user->id] = [
                'status' => $found && $found->isActive() ? 'Active' : 'Inactive',
            ];
        }
    }

    public function save(): void
    {
        if (! $this->isWhitelabelEnabled()) {
            throw ValidationException::withMessages([
                'rows' => 'Taxation API is not enabled for your white-label. Contact admin.',
            ]);
        }

        foreach ($this->developers() as $user) {
            $status = (string) ($this->rows[(string) $user->id]['status'] ?? 'Inactive');

            UserPlanApiAccess::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'service' => TaxationCatalog::SERVICE_ACCESS_KEY,
                ],
                [
                    'per_call_fee' => 0,
                    'status' => $status === 'Active',
                ],
            );
        }

        Notification::make()
            ->title('Developer taxation access saved')
            ->success()
            ->send();
    }
}
