<?php

namespace App\Filament\Resources\Whitelabels\Pages;

use App\Enums\OnboardingStatus;
use App\Enums\Role;
use App\Filament\Resources\Whitelabels\WhitelabelResource;
use App\Models\User;
use App\Models\Whitelabel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateWhitelabel extends CreateRecord
{
    protected static string $resource = WhitelabelResource::class;

    /** @var array{name: string, email: string, password: string}|null */
    protected ?array $ownerPayload = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->ownerPayload = [
            'name' => (string) $data['owner_name'],
            'email' => (string) $data['owner_email'],
            'password' => (string) $data['owner_password'],
        ];

        unset($data['owner_name'], $data['owner_email'], $data['owner_password']);

        $data['created_by'] = auth()->id();
        $data['wallet_balance'] = 0;
        $data['brand_name'] = filled($data['brand_name'] ?? null) ? $data['brand_name'] : $data['name'];

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Whitelabel $whitelabel */
        $whitelabel = $this->record;

        if (! $this->ownerPayload) {
            return;
        }

        DB::transaction(function () use ($whitelabel): void {
            $owner = User::query()->create([
                'name' => $this->ownerPayload['name'],
                'email' => $this->ownerPayload['email'],
                'password' => $this->ownerPayload['password'],
                'whitelabel_id' => $whitelabel->id,
                'onboarding_status' => OnboardingStatus::Approved,
                'approved_at' => now(),
                'wallet_balance' => 0,
            ]);

            $owner->assignRole(Role::Whitelabel->value);

            $whitelabel->update([
                'owner_user_id' => $owner->id,
            ]);
        });
    }
}
