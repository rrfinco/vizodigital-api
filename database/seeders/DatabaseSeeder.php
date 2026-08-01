<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            CmsFoundationSeeder::class,
            ApiCredentialSeeder::class,
            AuthDocumentationSeeder::class,
            RechargeDocumentationSeeder::class,
            WalletDocumentationSeeder::class,
            BillPaymentDocumentationSeeder::class,
            PlanApiDocumentationSeeder::class,
        ]);
    }
}
