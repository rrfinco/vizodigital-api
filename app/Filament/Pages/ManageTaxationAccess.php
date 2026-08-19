<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Services\Taxation\TaxationCatalog;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class ManageTaxationAccess extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'Taxation';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'API access';

    protected static ?string $title = 'Taxation API access (B2C)';

    protected string $view = 'filament.admin.pages.manage-taxation-access';

    /**
     * @var array<string, array{status: string}>
     */
    public array $rows = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('users.manage') ?? false;
    }

    public function mount(): void
    {
        $this->hydrateRows();
    }

    /**
     * @return Collection<int, User>
     */
    public function developers(): Collection
    {
        return User::query()
            ->role(Role::Developer->value)
            ->whereNull('whitelabel_id')
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
            ->title('Taxation API access saved')
            ->success()
            ->send();
    }
}
