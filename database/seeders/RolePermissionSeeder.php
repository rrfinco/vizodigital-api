<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        $all = collect(PermissionEnum::staffPermissions())->map->value->all();

        $superAdmin = Role::findOrCreate(RoleEnum::SuperAdmin->value, 'web');
        $superAdmin->syncPermissions($all);

        $admin = Role::findOrCreate(RoleEnum::Admin->value, 'web');
        $admin->syncPermissions($all);

        $editor = Role::findOrCreate(RoleEnum::Editor->value, 'web');
        $editor->syncPermissions([
            PermissionEnum::DocsViewAdmin->value,
            PermissionEnum::DocsCreate->value,
            PermissionEnum::DocsUpdate->value,
            PermissionEnum::DocsPreview->value,
            PermissionEnum::VersionsManage->value,
            PermissionEnum::EnvironmentsManage->value,
        ]);

        $viewer = Role::findOrCreate(RoleEnum::Viewer->value, 'web');
        $viewer->syncPermissions([
            PermissionEnum::DocsViewAdmin->value,
            PermissionEnum::DocsPreview->value,
            PermissionEnum::AnalyticsView->value,
        ]);

        Role::findOrCreate(RoleEnum::Developer->value, 'web')
            ->syncPermissions([
                PermissionEnum::ApiKeysManage->value,
            ]);
    }
}
