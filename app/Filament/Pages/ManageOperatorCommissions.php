<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserOperatorCommission;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ManageOperatorCommissions extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 25;

    protected static ?string $navigationLabel = 'Operator commissions';

    protected static ?string $title = 'Recharge operator commissions';

    protected string $view = 'filament.admin.pages.manage-operator-commissions';

    public ?int $selectedUserId = null;

    /**
     * @var array<string, array{commission_percentage: string, status: string, operator_id: int}>
     */
    public array $rows = [];

    public const OPERATORS = [
        // Mobile
        ['id' => 1, 'operator_name' => 'Airtel Prepaid', 'type' => 'mobile', 'sp_key' => 3, 'logo_path' => 'images/operators/airtel.svg', 'web_logo_domain' => 'airtel.in', 'default_commission' => '2.50', 'default_status' => 'Active'],
        ['id' => 2, 'operator_name' => 'Jio Prepaid', 'type' => 'mobile', 'sp_key' => 116, 'logo_path' => 'images/operators/jio.svg', 'web_logo_domain' => 'jio.com', 'default_commission' => '3.00', 'default_status' => 'Active'],
        ['id' => 3, 'operator_name' => 'Vi Prepaid (Vodafone)', 'type' => 'mobile', 'sp_key' => 37, 'logo_path' => 'images/operators/vi.svg', 'web_logo_domain' => 'myvi.in', 'default_commission' => '3.50', 'default_status' => 'Active'],
        ['id' => 4, 'operator_name' => 'Vi Prepaid (Idea)', 'type' => 'mobile', 'sp_key' => 12, 'logo_path' => 'images/operators/vi.svg', 'web_logo_domain' => 'myvi.in', 'default_commission' => '3.50', 'default_status' => 'Active'],
        ['id' => 5, 'operator_name' => 'BSNL Prepaid', 'type' => 'mobile', 'sp_key' => 4, 'logo_path' => 'images/operators/bsnl.svg', 'web_logo_domain' => 'bsnl.co.in', 'default_commission' => '4.00', 'default_status' => 'Active'],
        ['id' => 6, 'operator_name' => 'BSNL Special Tariff', 'type' => 'mobile', 'sp_key' => 5, 'logo_path' => 'images/operators/bsnl.svg', 'web_logo_domain' => 'bsnl.co.in', 'default_commission' => '4.00', 'default_status' => 'Active'],
        // DTH
        ['id' => 7, 'operator_name' => 'Airtel Digital TV', 'type' => 'dth', 'sp_key' => 51, 'logo_path' => 'images/operators/airteldth.svg', 'web_logo_domain' => 'airtel.in', 'default_commission' => '3.00', 'default_status' => 'Active'],
        ['id' => 8, 'operator_name' => 'Dish TV', 'type' => 'dth', 'sp_key' => 53, 'logo_path' => 'images/operators/dishtv.svg', 'web_logo_domain' => 'dishtv.in', 'default_commission' => '3.50', 'default_status' => 'Active'],
        ['id' => 9, 'operator_name' => 'Sun Direct', 'type' => 'dth', 'sp_key' => 54, 'logo_path' => 'images/operators/dishtv.svg', 'web_logo_domain' => 'sundirect.in', 'default_commission' => '3.50', 'default_status' => 'Active'],
        ['id' => 10, 'operator_name' => 'Tata Sky (Tata Play)', 'type' => 'dth', 'sp_key' => 55, 'logo_path' => 'images/operators/tataplay.svg', 'web_logo_domain' => 'tataplay.com', 'default_commission' => '3.00', 'default_status' => 'Active'],
        ['id' => 11, 'operator_name' => 'Videocon D2h', 'type' => 'dth', 'sp_key' => 56, 'logo_path' => 'images/operators/d2h.svg', 'web_logo_domain' => 'd2h.com', 'default_commission' => '3.00', 'default_status' => 'Active'],
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
        // developer role wale users (KYC already flow mein handle hota hai)
        return User::query()
            ->role(Role::Developer->value)
            ->orderBy('name')
            ->get();
    }

    private function operatorKey(array $operator): string
    {
        // Livewire property paths ke liye colon/hyphen avoid karo.
        return $operator['type'].'_'.$operator['sp_key'];
    }

    public function operatorLogoSrc(array $operator): string
    {
        $domain = $operator['web_logo_domain'] ?? null;
        if (! is_string($domain) || $domain === '') {
            return asset($operator['logo_path'] ?? '');
        }

        // Web logos: use a favicon endpoint (browser will fetch it).
        return 'https://www.google.com/s2/favicons?sz=128&domain='.rawurlencode(trim($domain));
    }

    public function hydrateRows(): void
    {
        $this->rows = [];

        $operators = self::OPERATORS;

        if (! $this->selectedUserId) {
            foreach ($operators as $operator) {
                $key = $this->operatorKey($operator);
                $this->rows[$key] = [
                    'operator_id' => $operator['id'],
                    'commission_percentage' => $operator['default_commission'],
                    'status' => $operator['default_status'],
                ];
            }

            return;
        }

        $existing = UserOperatorCommission::query()
            ->where('user_id', $this->selectedUserId)
            ->get()
            ->keyBy(fn (UserOperatorCommission $row): string => $row->operator_type.'_'.$row->operator_sp_key);

        foreach ($operators as $operator) {
            $key = $this->operatorKey($operator);
            $found = $existing->get($key);

            $this->rows[$key] = [
                'operator_id' => $operator['id'],
                'commission_percentage' => $found ? number_format((float) $found->commission_percentage, 2, '.', '') : $operator['default_commission'],
                'status' => $found ? ((bool) $found->status ? 'Active' : 'Inactive') : $operator['default_status'],
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

        foreach (self::OPERATORS as $operator) {
            $key = $this->operatorKey($operator);

            $commission = (string) ($this->rows[$key]['commission_percentage'] ?? $operator['default_commission']);
            $status = (string) ($this->rows[$key]['status'] ?? $operator['default_status']);

            // Basic validation for commission %.
            if (! is_numeric($commission)) {
                throw ValidationException::withMessages([
                    'commission_percentage' => 'Commission must be numeric.',
                ]);
            }

            $commissionFloat = (float) $commission;
            if ($commissionFloat < 0 || $commissionFloat > 100) {
                throw ValidationException::withMessages([
                    'commission_percentage' => 'Commission must be between 0 and 100.',
                ]);
            }

            $statusBool = $status === 'Active';

            UserOperatorCommission::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'operator_type' => $operator['type'],
                    'operator_sp_key' => $operator['sp_key'],
                ],
                [
                    'commission_percentage' => $commissionFloat,
                    'status' => $statusBool,
                ],
            );
        }

        Notification::make()
            ->title('Operator commissions saved')
            ->success()
            ->send();
    }

    /**
     * @return list<array{
     *   id: int,
     *   operator_name: string,
     *   type: string,
     *   sp_key: int,
     *   logo_path: string,
     *   web_logo_domain: string,
     *   default_commission: string,
     *   default_status: string
     * }>
     */
    public function operators(): array
    {
        return self::OPERATORS;
    }

    public function developerUsersForSelect(): Collection
    {
        return $this->developerUsers()
            ->map(fn (User $u) => ['id' => $u->id, 'label' => $u->company_name ?: $u->name])
            ->values();
    }
}

