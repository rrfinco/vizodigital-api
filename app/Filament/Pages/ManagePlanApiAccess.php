<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ManagePlanApiAccess extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 27;

    protected static ?string $navigationLabel = 'Plan API access';

    protected static ?string $title = 'Plan & operator API access';

    protected string $view = 'filament.admin.pages.manage-plan-api-access';

    public ?int $selectedUserId = null;

    /**
     * @var array<string, array{per_call_fee: string, status: string}>
     */
    public array $rows = [];

    public const SERVICES = [
        [
            'key' => 'operator_fetch',
            'label' => 'Mobile operator find',
            'description' => 'Detect which operator a mobile number belongs to.',
            'default_fee' => '0.10',
        ],
        [
            'key' => 'operator_plan_fetch',
            'label' => 'Mobile plan fetch',
            'description' => 'Fetch prepaid recharge plans for an operator and circle.',
            'default_fee' => '0.10',
        ],
        [
            'key' => 'dth_plan_fetch',
            'label' => 'DTH plan fetch',
            'description' => 'Fetch DTH pack / plan information.',
            'default_fee' => '0.10',
        ],
        [
            'key' => 'dth_info',
            'label' => 'DTH customer info',
            'description' => 'Fetch DTH customer name, balance, and minimum recharge.',
            'default_fee' => '0.10',
        ],
        [
            'key' => 'credit_card_fetch',
            'label' => 'Credit card bill fetch',
            'description' => 'Fetch credit card bill details (due amount, fetch_id) before payment.',
            'default_fee' => '0.10',
        ],
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('users.manage') ?? false;
    }

    public function mount(): void
    {
        $this->selectedUserId = $this->developerUsers()->first()?->id;
        $this->hydrateRows();
    }

    /**
     * @return Collection<int, User>
     */
    private function developerUsers(): Collection
    {
        // Platform B2C developers only — WL developers are managed by partners.
        return User::query()
            ->role(Role::Developer->value)
            ->whereNull('whitelabel_id')
            ->orderBy('name')
            ->get();
    }

    public function hydrateRows(): void
    {
        $this->rows = [];

        if (! $this->selectedUserId) {
            foreach (self::SERVICES as $service) {
                $this->rows[$service['key']] = [
                    'per_call_fee' => $service['default_fee'],
                    'status' => 'Inactive',
                ];
            }

            return;
        }

        $existing = UserPlanApiAccess::query()
            ->where('user_id', $this->selectedUserId)
            ->get()
            ->keyBy('service');

        foreach (self::SERVICES as $service) {
            $found = $existing->get($service['key']);

            $this->rows[$service['key']] = [
                'per_call_fee' => $found
                    ? number_format((float) $found->per_call_fee, 2, '.', '')
                    : $service['default_fee'],
                'status' => $found && $found->isActive() ? 'Active' : 'Inactive',
            ];
        }
    }

    public function updatedSelectedUserId(): void
    {
        $this->hydrateRows();
    }

    public function save(): void
    {
        if (! $this->selectedUserId) {
            throw ValidationException::withMessages([
                'selectedUserId' => 'Please select a user.',
            ]);
        }

        $user = User::query()->find($this->selectedUserId);
        if (! $user) {
            throw ValidationException::withMessages([
                'selectedUserId' => 'Selected user not found.',
            ]);
        }

        foreach (self::SERVICES as $service) {
            $key = $service['key'];
            $fee = (string) ($this->rows[$key]['per_call_fee'] ?? $service['default_fee']);
            $status = (string) ($this->rows[$key]['status'] ?? 'Inactive');

            if (! is_numeric($fee)) {
                throw ValidationException::withMessages([
                    'per_call_fee' => 'Per-call fee must be numeric.',
                ]);
            }

            $feeFloat = round((float) $fee, 2);
            if ($feeFloat < 0) {
                throw ValidationException::withMessages([
                    'per_call_fee' => 'Per-call fee cannot be negative.',
                ]);
            }

            UserPlanApiAccess::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'service' => $key,
                ],
                [
                    'per_call_fee' => $feeFloat,
                    'status' => $status === 'Active',
                ],
            );
        }

        Notification::make()
            ->title('Plan API access saved')
            ->success()
            ->send();
    }

    /**
     * @return list<array{key: string, label: string, description: string, default_fee: string}>
     */
    public function services(): array
    {
        return self::SERVICES;
    }

    public function developerUsersForSelect(): Collection
    {
        return $this->developerUsers()
            ->map(fn (User $u) => ['id' => $u->id, 'label' => $u->company_name ?: $u->name])
            ->values();
    }
}
