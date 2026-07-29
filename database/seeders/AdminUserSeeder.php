<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::flush();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@portal.test'],
            [
                'name' => 'Portal Admin',
                'password' => 'password',
                'email_verified_at' => now(),
                'onboarding_status' => 'approved',
            ]
        );

        $admin->syncRoles([RoleEnum::SuperAdmin->value]);

        $user = User::query()->updateOrCreate(
            ['email' => 'user@portal.test'],
            [
                'name' => 'Portal User',
                'password' => 'password',
                'email_verified_at' => now(),
                'onboarding_status' => 'approved',
                'company_name' => 'Demo Merchant',
                'approved_at' => now(),
            ]
        );

        $user->syncRoles([RoleEnum::Developer->value]);
    }
}
