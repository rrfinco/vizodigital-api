<?php

namespace App\Filament\Partner\Pages;

use App\Enums\Role;
use App\Filament\Pages\ManagePlanApiAccess;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Models\WhitelabelPlanApiAccess;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ManageDeveloperPlanApiAccess extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'Commissions';

    protected static ?int $navigationSort = 45;

    protected static ?string $navigationLabel = 'Developer Plan API';

    protected static ?string $title = 'Developer Plan API access';

    protected string $view = 'filament.partner.pages.manage-developer-plan-api-access';

    public ?int $selectedUserId = null;

    /**
     * @var array<string, array{per_call_fee: string, status: string, min_fee: string}>
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

    /**
     * @return array<string, array{per_call_fee: float, status: bool}>
     */
    private function whitelabelCaps(): array
    {
        $wlId = auth()->user()?->whitelabel_id;
        $caps = [];

        foreach (ManagePlanApiAccess::SERVICES as $service) {
            $resolved = $wlId
                ? WhitelabelPlanApiAccess::resolveFor((int) $wlId, $service['key'])
                : null;

            $caps[$service['key']] = $resolved ?? [
                'per_call_fee' => (float) $service['default_fee'],
                'status' => false,
            ];
        }

        return $caps;
    }

    public function hydrateRows(): void
    {
        $this->rows = [];
        $caps = $this->whitelabelCaps();

        if (! $this->selectedUserId) {
            foreach (ManagePlanApiAccess::SERVICES as $service) {
                $key = $service['key'];
                $cap = $caps[$key];
                $min = number_format($cap['per_call_fee'], 2, '.', '');

                $this->rows[$key] = [
                    'per_call_fee' => $min,
                    'status' => $cap['status'] ? 'Active' : 'Inactive',
                    'min_fee' => $min,
                ];
            }

            return;
        }

        $existing = UserPlanApiAccess::query()
            ->where('user_id', $this->selectedUserId)
            ->get()
            ->keyBy('service');

        foreach (ManagePlanApiAccess::SERVICES as $service) {
            $key = $service['key'];
            $found = $existing->get($key);
            $cap = $caps[$key];
            $min = number_format($cap['per_call_fee'], 2, '.', '');

            $this->rows[$key] = [
                'per_call_fee' => $found
                    ? number_format((float) $found->per_call_fee, 2, '.', '')
                    : $min,
                'status' => $found
                    ? ($found->isActive() ? 'Active' : 'Inactive')
                    : ($cap['status'] ? 'Active' : 'Inactive'),
                'min_fee' => $min,
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

        foreach (ManagePlanApiAccess::SERVICES as $service) {
            $key = $service['key'];
            $cap = $caps[$key];

            $fee = (string) ($this->rows[$key]['per_call_fee'] ?? '0');
            $status = (string) ($this->rows[$key]['status'] ?? 'Inactive');

            if (! is_numeric($fee)) {
                throw ValidationException::withMessages([
                    "rows.{$key}.per_call_fee" => "Per-call fee must be numeric for {$service['label']}.",
                ]);
            }

            $feeFloat = round((float) $fee, 2);
            if ($feeFloat < 0) {
                throw ValidationException::withMessages([
                    "rows.{$key}.per_call_fee" => "Per-call fee cannot be negative for {$service['label']}.",
                ]);
            }

            if ($feeFloat + 0.00001 < $cap['per_call_fee']) {
                throw ValidationException::withMessages([
                    "rows.{$key}.per_call_fee" => "{$service['label']}: min allowed is ₹{$cap['per_call_fee']} (your white-label cost).",
                ]);
            }

            $statusBool = $status === 'Active';
            if ($statusBool && ! $cap['status']) {
                throw ValidationException::withMessages([
                    "rows.{$key}.status" => "{$service['label']} is inactive on your white-label plan.",
                ]);
            }

            UserPlanApiAccess::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'service' => $key,
                ],
                [
                    'per_call_fee' => $feeFloat,
                    'status' => $statusBool,
                ],
            );
        }

        Notification::make()
            ->title('Developer Plan API access saved')
            ->success()
            ->send();
    }

    /**
     * @return list<array{key: string, label: string, description: string, default_fee: string}>
     */
    public function services(): array
    {
        return ManagePlanApiAccess::SERVICES;
    }

    public function developerUsersForSelect(): Collection
    {
        return $this->developerUsers()
            ->map(fn (User $u) => ['id' => $u->id, 'label' => $u->company_name ?: $u->name])
            ->values();
    }
}
