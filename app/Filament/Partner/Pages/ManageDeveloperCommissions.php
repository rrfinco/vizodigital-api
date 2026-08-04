<?php

namespace App\Filament\Partner\Pages;

use App\Enums\Role;
use App\Filament\Pages\ManageOperatorCommissions;
use App\Models\User;
use App\Models\UserOperatorCommission;
use App\Models\WhitelabelOperatorCommission;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ManageDeveloperCommissions extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Commissions';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Developer recharge';

    protected static ?string $title = 'Developer recharge commissions';

    protected string $view = 'filament.partner.pages.manage-developer-commissions';

    public ?int $selectedUserId = null;

    /**
     * @var array<string, array{commission_percentage: string, status: string, operator_id: int, max_commission: string}>
     */
    public array $rows = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('whitelabel-commissions.manage') ?? false;
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

    private function operatorKey(array $operator): string
    {
        return $operator['type'].'_'.$operator['sp_key'];
    }

    public function operatorLogoSrc(array $operator): string
    {
        $domain = $operator['web_logo_domain'] ?? null;
        if (! is_string($domain) || $domain === '') {
            return asset($operator['logo_path'] ?? '');
        }

        return 'https://www.google.com/s2/favicons?sz=128&domain='.rawurlencode(trim($domain));
    }

    /**
     * @return array<string, array{commission_percentage: float, status: bool}>
     */
    private function whitelabelCaps(): array
    {
        $wlId = auth()->user()?->whitelabel_id;
        $caps = [];

        foreach (ManageOperatorCommissions::OPERATORS as $operator) {
            $key = $this->operatorKey($operator);
            $caps[$key] = WhitelabelOperatorCommission::resolveFor(
                (int) $wlId,
                $operator['type'],
                (int) $operator['sp_key'],
                (float) $operator['default_commission']
            );
        }

        return $caps;
    }

    public function hydrateRows(): void
    {
        $this->rows = [];
        $operators = ManageOperatorCommissions::OPERATORS;
        $caps = $this->whitelabelCaps();

        if (! $this->selectedUserId) {
            foreach ($operators as $operator) {
                $key = $this->operatorKey($operator);
                $cap = $caps[$key];
                $this->rows[$key] = [
                    'operator_id' => $operator['id'],
                    'commission_percentage' => number_format($cap['commission_percentage'], 2, '.', ''),
                    'status' => $cap['status'] ? 'Active' : 'Inactive',
                    'max_commission' => number_format($cap['commission_percentage'], 2, '.', ''),
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
            $cap = $caps[$key];
            $max = number_format($cap['commission_percentage'], 2, '.', '');

            $this->rows[$key] = [
                'operator_id' => $operator['id'],
                'commission_percentage' => $found
                    ? number_format((float) $found->commission_percentage, 2, '.', '')
                    : $max,
                'status' => $found
                    ? ((bool) $found->status ? 'Active' : 'Inactive')
                    : ($cap['status'] ? 'Active' : 'Inactive'),
                'max_commission' => $max,
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
                'selectedUserId' => 'Please select a developer.',
            ]);
        }

        $wlId = auth()->user()?->whitelabel_id;
        $user = User::query()
            ->role(Role::Developer->value)
            ->where('whitelabel_id', $wlId)
            ->find($this->selectedUserId);

        if (! $user) {
            throw ValidationException::withMessages([
                'selectedUserId' => 'Selected developer not found in your white-label.',
            ]);
        }

        $caps = $this->whitelabelCaps();

        foreach (ManageOperatorCommissions::OPERATORS as $operator) {
            $key = $this->operatorKey($operator);
            $cap = $caps[$key];

            $commission = (string) ($this->rows[$key]['commission_percentage'] ?? '0');
            $status = (string) ($this->rows[$key]['status'] ?? 'Active');

            if (! is_numeric($commission)) {
                throw ValidationException::withMessages([
                    "rows.{$key}.commission_percentage" => "Commission must be numeric for {$operator['operator_name']}.",
                ]);
            }

            $commissionFloat = (float) $commission;
            if ($commissionFloat < 0 || $commissionFloat > 100) {
                throw ValidationException::withMessages([
                    "rows.{$key}.commission_percentage" => "Commission must be between 0 and 100 for {$operator['operator_name']}.",
                ]);
            }

            if ($commissionFloat > $cap['commission_percentage'] + 0.00001) {
                throw ValidationException::withMessages([
                    "rows.{$key}.commission_percentage" => "{$operator['operator_name']}: max allowed is {$cap['commission_percentage']}% (your white-label rate).",
                ]);
            }

            $statusBool = $status === 'Active';
            if ($statusBool && ! $cap['status']) {
                throw ValidationException::withMessages([
                    "rows.{$key}.status" => "{$operator['operator_name']} is inactive on your white-label plan.",
                ]);
            }

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
            ->title('Developer commissions saved')
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
        return ManageOperatorCommissions::OPERATORS;
    }

    public function developerUsersForSelect(): Collection
    {
        return $this->developerUsers()
            ->map(fn (User $u) => ['id' => $u->id, 'label' => $u->company_name ?: $u->name])
            ->values();
    }
}
