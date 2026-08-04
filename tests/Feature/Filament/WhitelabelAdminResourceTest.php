<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Enums\WhitelabelStatus;
use App\Filament\Resources\WhitelabelFloatRequests\Pages\ViewWhitelabelFloatRequest;
use App\Filament\Resources\Whitelabels\Pages\CreateWhitelabel;
use App\Filament\Resources\Whitelabels\Pages\ListWhitelabels;
use App\Models\User;
use App\Models\Whitelabel;
use App\Models\WhitelabelFloatRequest;
use App\Models\WhitelabelOperatorCommission;
use App\Services\Whitelabel\WhitelabelFloatService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WhitelabelAdminResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::Admin->value);
    }

    public function test_admin_can_list_whitelabels(): void
    {
        $wl = Whitelabel::factory()->create(['name' => 'Partner One']);

        $this->actingAs($this->admin)
            ->get('/admin/whitelabels')
            ->assertOk()
            ->assertSee('White-labels');

        Livewire::actingAs($this->admin)
            ->test(ListWhitelabels::class)
            ->assertCanSeeTableRecords([$wl]);
    }

    public function test_admin_can_create_whitelabel_with_owner(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateWhitelabel::class)
            ->fillForm([
                'name' => 'Acme Partner',
                'slug' => 'acme-partner',
                'status' => WhitelabelStatus::Active->value,
                'brand_name' => 'Acme',
                'owner_name' => 'Acme Owner',
                'owner_email' => 'owner@acme.test',
                'owner_password' => 'Password1!',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('whitelabels', [
            'slug' => 'acme-partner',
            'name' => 'Acme Partner',
        ]);

        $owner = User::query()->where('email', 'owner@acme.test')->first();
        $this->assertNotNull($owner);
        $this->assertTrue($owner->hasRole(Role::Whitelabel->value));
        $this->assertNotNull($owner->whitelabel_id);
        $this->assertSame($owner->id, Whitelabel::query()->where('slug', 'acme-partner')->value('owner_user_id'));
    }

    public function test_developer_cannot_access_whitelabel_admin(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole(Role::Developer->value);

        $this->actingAs($developer)
            ->get('/admin/whitelabels')
            ->assertRedirect();

        $this->assertGuest();
    }

    public function test_approve_float_request_credits_whitelabel_wallet(): void
    {
        $wl = Whitelabel::factory()->withFloat(100)->create();
        $owner = User::factory()->forWhitelabel($wl->id)->create();
        $owner->assignRole(Role::Whitelabel->value);
        $wl->update(['owner_user_id' => $owner->id]);

        $request = WhitelabelFloatRequest::query()->create([
            'whitelabel_id' => $wl->id,
            'requested_by' => $owner->id,
            'amount' => 500,
            'method' => WhitelabelFloatRequest::METHOD_BANK_TRANSFER,
            'status' => WhitelabelFloatRequest::STATUS_PENDING,
            'utr' => 'UTR999',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ViewWhitelabelFloatRequest::class, ['record' => $request->getKey()])
            ->callAction('approve', data: ['notes' => 'OK']);

        $this->assertSame(600.0, (float) $wl->fresh()->wallet_balance);
        $this->assertSame(WhitelabelFloatRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertDatabaseHas('whitelabel_wallet_transactions', [
            'whitelabel_id' => $wl->id,
            'type' => 'credit',
        ]);
    }

    public function test_reject_float_request_does_not_credit(): void
    {
        $wl = Whitelabel::factory()->withFloat(100)->create();
        $owner = User::factory()->forWhitelabel($wl->id)->create();
        $owner->assignRole(Role::Whitelabel->value);

        $request = WhitelabelFloatRequest::query()->create([
            'whitelabel_id' => $wl->id,
            'requested_by' => $owner->id,
            'amount' => 500,
            'method' => WhitelabelFloatRequest::METHOD_BANK_TRANSFER,
            'status' => WhitelabelFloatRequest::STATUS_PENDING,
            'utr' => 'UTR111',
        ]);

        app(WhitelabelFloatService::class)->reject($request, $this->admin, 'Bad UTR');

        $this->assertSame(100.0, (float) $wl->fresh()->wallet_balance);
        $this->assertSame(WhitelabelFloatRequest::STATUS_REJECTED, $request->fresh()->status);
    }

    public function test_whitelabel_commission_page_saves_rows(): void
    {
        $wl = Whitelabel::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(\App\Filament\Pages\ManageWhitelabelOperatorCommissions::class)
            ->set('selectedWhitelabelId', $wl->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            count(\App\Filament\Pages\ManageOperatorCommissions::OPERATORS),
            WhitelabelOperatorCommission::query()->where('whitelabel_id', $wl->id)->count()
        );

        $this->assertDatabaseHas('whitelabel_operator_commissions', [
            'whitelabel_id' => $wl->id,
            'operator_type' => 'mobile',
            'operator_sp_key' => 116,
        ]);
    }

    public function test_whitelabel_bill_commission_page_saves_row(): void
    {
        $wl = Whitelabel::factory()->create();

        $component = Livewire::actingAs($this->admin)
            ->test(\App\Filament\Pages\ManageWhitelabelBillOperatorCommissions::class)
            ->set('selectedWhitelabelId', $wl->id);

        $instance = $component->instance();
        $instance->commissionRows = [
            'CC01' => [
                'commission_type' => 'percentage',
                'commission_value' => '1.75',
                'status' => 'Active',
            ],
        ];
        $instance->saveCommissions();

        $this->assertDatabaseHas('whitelabel_bill_operator_commissions', [
            'whitelabel_id' => $wl->id,
            'opcode' => 'CC01',
            'commission_type' => 'percentage',
            'commission_value' => 1.75,
        ]);
    }

    public function test_whitelabel_plan_api_page_saves_rows(): void
    {
        $wl = Whitelabel::factory()->create();

        $component = Livewire::actingAs($this->admin)
            ->test(\App\Filament\Pages\ManageWhitelabelPlanApiAccess::class)
            ->set('selectedWhitelabelId', $wl->id);

        $instance = $component->instance();
        $instance->rows['operator_fetch'] = [
            'per_call_fee' => '0.12',
            'status' => 'Active',
        ];
        $instance->save();

        $this->assertDatabaseHas('whitelabel_plan_api_access', [
            'whitelabel_id' => $wl->id,
            'service' => 'operator_fetch',
            'per_call_fee' => 0.12,
            'status' => 1,
        ]);
    }
}
