<?php

namespace Tests\Feature\Whitelabel;

use App\Enums\Role;
use App\Enums\WhitelabelStatus;
use App\Models\User;
use App\Models\Whitelabel;
use App\Models\WhitelabelDomain;
use App\Models\WhitelabelFloatRequest;
use App\Models\WhitelabelOperatorCommission;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhitelabelSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_whitelabel_wallet_debit_and_credit_update_ledger(): void
    {
        $wl = Whitelabel::factory()->withFloat(1000)->create();

        $wl->debitWallet(250, 'Test debit');
        $wl->refresh();

        $this->assertSame(750.0, (float) $wl->wallet_balance);
        $this->assertDatabaseHas('whitelabel_wallet_transactions', [
            'whitelabel_id' => $wl->id,
            'type' => 'debit',
            'description' => 'Test debit',
        ]);

        $wl->creditWallet(100, 'Test credit');
        $wl->refresh();

        $this->assertSame(850.0, (float) $wl->wallet_balance);
        $this->assertDatabaseCount('whitelabel_wallet_transactions', 2);
    }

    public function test_user_can_belong_to_whitelabel_and_partner_role_gates_panel(): void
    {
        $wl = Whitelabel::factory()->create();
        $owner = User::factory()->forWhitelabel($wl->id)->create();
        $owner->assignRole(Role::Whitelabel->value);
        $wl->update(['owner_user_id' => $owner->id]);

        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'Partner.Example.COM',
            'is_primary' => true,
        ]);

        $this->assertTrue($owner->isWhitelabelPartner());
        $this->assertTrue($owner->belongsToWhitelabel());
        $this->assertSame('partner.example.com', $wl->domains()->first()->host);
        $this->assertTrue($wl->isActive());
        $this->assertSame(WhitelabelStatus::Active, $wl->status);
    }

    public function test_float_request_and_operator_commission_rows_persist(): void
    {
        $wl = Whitelabel::factory()->create();
        $owner = User::factory()->forWhitelabel($wl->id)->create();
        $owner->assignRole(Role::Whitelabel->value);

        WhitelabelFloatRequest::query()->create([
            'whitelabel_id' => $wl->id,
            'requested_by' => $owner->id,
            'amount' => 5000,
            'method' => WhitelabelFloatRequest::METHOD_BANK_TRANSFER,
            'status' => WhitelabelFloatRequest::STATUS_PENDING,
            'utr' => 'UTR123',
        ]);

        WhitelabelOperatorCommission::query()->create([
            'whitelabel_id' => $wl->id,
            'operator_type' => 'mobile',
            'operator_sp_key' => 11,
            'commission_percentage' => 1.5,
            'status' => true,
        ]);

        $this->assertDatabaseHas('whitelabel_float_requests', [
            'whitelabel_id' => $wl->id,
            'status' => 'pending',
            'utr' => 'UTR123',
        ]);
        $this->assertDatabaseHas('whitelabel_operator_commissions', [
            'whitelabel_id' => $wl->id,
            'operator_sp_key' => 11,
        ]);
    }
}
