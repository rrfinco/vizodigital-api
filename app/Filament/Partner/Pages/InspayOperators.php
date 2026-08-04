<?php

namespace App\Filament\Partner\Pages;

use App\Enums\Role;
use App\Filament\Concerns\InteractsWithInspayOperators;
use App\Models\User;
use App\Models\UserBillOperatorCommission;
use App\Models\WhitelabelBillOperatorCommission;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;
use UnitEnum;

class InspayOperators extends Page
{
    use InteractsWithInspayOperators;
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Commissions';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Developer bill opcodes';

    protected static ?string $title = 'Developer bill payment commissions';

    protected static ?string $slug = 'inspay-operators';

    protected string $view = 'filament.pages.inspay-operators';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('whitelabel-commissions.manage') ?? false;
    }

    public function mount(): void
    {
        $this->selectedUserId = $this->developerUsers()->first()?->id;
        $this->hydrateCommissionRows();
    }

    public function canManageCommissions(): bool
    {
        return true;
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

    public function developerUsersForSelect(): Collection
    {
        return $this->developerUsers()
            ->map(fn (User $u) => ['id' => $u->id, 'label' => $u->company_name ?: $u->name])
            ->values();
    }

    public function saveCommissions(): void
    {
        if (! $this->canManageCommissions()) {
            return;
        }

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

        $codes = array_keys($this->commissionRows);
        if ($codes === []) {
            Notification::make()
                ->title('Nothing to save')
                ->warning()
                ->send();

            return;
        }

        foreach ($this->commissionRows as $opcode => $row) {
            $type = (string) ($row['commission_type'] ?? UserBillOperatorCommission::TYPE_PERCENTAGE);
            $value = (string) ($row['commission_value'] ?? '0');
            $status = (string) ($row['status'] ?? 'Active');

            if (! in_array($type, [UserBillOperatorCommission::TYPE_PERCENTAGE, UserBillOperatorCommission::TYPE_FLAT], true)) {
                throw ValidationException::withMessages([
                    "commissionRows.{$opcode}.commission_type" => "Invalid commission type for opcode {$opcode}.",
                ]);
            }

            if (! is_numeric($value)) {
                throw ValidationException::withMessages([
                    "commissionRows.{$opcode}.commission_value" => "Commission must be numeric for opcode {$opcode}.",
                ]);
            }

            $valueFloat = (float) $value;
            if ($valueFloat < 0) {
                throw ValidationException::withMessages([
                    "commissionRows.{$opcode}.commission_value" => "Commission cannot be negative for opcode {$opcode}.",
                ]);
            }

            if ($type === UserBillOperatorCommission::TYPE_PERCENTAGE && $valueFloat > 100) {
                throw ValidationException::withMessages([
                    "commissionRows.{$opcode}.commission_value" => "Percentage commission must be between 0 and 100 for opcode {$opcode}.",
                ]);
            }

            $wlCap = WhitelabelBillOperatorCommission::resolveFor((int) $wlId, (string) $opcode);

            if ($status === 'Active' && ! $wlCap['status']) {
                throw ValidationException::withMessages([
                    "commissionRows.{$opcode}.status" => "Opcode {$opcode} is inactive on your white-label plan.",
                ]);
            }

            // Cap only when WL has an explicit configured rate (or same type comparison is meaningful).
            $wlHasRow = WhitelabelBillOperatorCommission::query()
                ->where('whitelabel_id', $wlId)
                ->where('opcode', (string) $opcode)
                ->exists();

            if ($wlHasRow) {
                if ($type !== $wlCap['commission_type']) {
                    throw ValidationException::withMessages([
                        "commissionRows.{$opcode}.commission_type" => "Opcode {$opcode}: type must match your white-label rate ({$wlCap['commission_type']}).",
                    ]);
                }

                if ($valueFloat > $wlCap['commission_value'] + 0.00001) {
                    throw ValidationException::withMessages([
                        "commissionRows.{$opcode}.commission_value" => "Opcode {$opcode}: max allowed is {$wlCap['commission_value']} (your white-label rate).",
                    ]);
                }
            }

            UserBillOperatorCommission::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'opcode' => (string) $opcode,
                ],
                [
                    'commission_type' => $type,
                    'commission_value' => $valueFloat,
                    'status' => $status === 'Active',
                ],
            );
        }

        Notification::make()
            ->title('Bill operator commissions saved')
            ->body('Saved '.count($codes).' operator(s) on this page.')
            ->success()
            ->send();
    }
}
