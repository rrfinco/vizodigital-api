<?php

namespace Tests\Feature\Filament;

use App\Enums\OnboardingStatus;
use App\Enums\Role;
use App\Filament\Partner\Pages\FloatWallet;
use App\Filament\Partner\Pages\InspayOperators as PartnerInspayOperators;
use App\Filament\Partner\Pages\ManageDeveloperCommissions;
use App\Filament\Partner\Pages\ManageDeveloperPlanApiAccess;
use App\Filament\Partner\Pages\MyPlanApiRates;
use App\Filament\Partner\Pages\MyRechargeRates;
use App\Filament\Partner\Resources\Developers\Pages\ListDevelopers;
use App\Filament\Partner\Resources\KycApplications\Pages\ListKycApplications;
use App\Models\User;
use App\Models\Whitelabel;
use App\Models\WhitelabelBillOperatorCommission;
use App\Models\WhitelabelDomain;
use App\Models\WhitelabelFloatRequest;
use App\Models\WhitelabelOperatorCommission;
use App\Models\WhitelabelPlanApiAccess;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerPanelTest extends TestCase
{
    use RefreshDatabase;

    protected Whitelabel $whitelabel;

    protected User $partner;

    protected string $partnerHost = 'acme.partner.test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);

        $this->whitelabel = Whitelabel::factory()->withFloat(250)->create([
            'name' => 'Acme WL',
            'brand_name' => 'Acme',
        ]);

        WhitelabelDomain::query()->create([
            'whitelabel_id' => $this->whitelabel->id,
            'host' => $this->partnerHost,
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $this->partner = User::factory()->forWhitelabel($this->whitelabel->id)->create([
            'email' => 'owner@acme.test',
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $this->partner->assignRole(Role::Whitelabel->value);
        $this->whitelabel->update(['owner_user_id' => $this->partner->id]);

        $this->withServerVariables([
            'HTTP_HOST' => $this->partnerHost,
            'SERVER_NAME' => $this->partnerHost,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('partner'));
    }

    private function partnerUrl(string $path = '/partner'): string
    {
        return 'http://'.$this->partnerHost.$path;
    }

    public function test_partner_panel_unavailable_on_platform_host(): void
    {
        $this->withServerVariables([
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
        ]);

        $this->get('http://localhost/partner/login')->assertNotFound();
    }

    public function test_partner_can_access_panel_and_dashboard(): void
    {
        $this->actingAs($this->partner)
            ->get($this->partnerUrl())
            ->assertOk();
    }

    public function test_whitelabel_root_redirects_to_partner(): void
    {
        $this->get($this->partnerUrl('/'))
            ->assertRedirect('/partner');
    }

    public function test_developer_cannot_access_partner_panel(): void
    {
        $developer = User::factory()->forWhitelabel($this->whitelabel->id)->create();
        $developer->assignRole(Role::Developer->value);

        $this->actingAs($developer)
            ->get($this->partnerUrl())
            ->assertRedirect();

        $this->assertGuest();
    }

    public function test_partner_cannot_access_foreign_whitelabel_domain(): void
    {
        $other = Whitelabel::factory()->create();
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $other->id,
            'host' => 'other.partner.test',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $this->actingAs($this->partner)
            ->get('http://other.partner.test/partner')
            ->assertRedirect();

        $this->assertGuest();
    }

    public function test_partner_can_submit_float_request(): void
    {
        Livewire::actingAs($this->partner)
            ->test(FloatWallet::class)
            ->set('amount', 1000)
            ->set('utr', 'UTRACME123456')
            ->call('submitFloatRequest')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('whitelabel_float_requests', [
            'whitelabel_id' => $this->whitelabel->id,
            'requested_by' => $this->partner->id,
            'amount' => 1000,
            'status' => WhitelabelFloatRequest::STATUS_PENDING,
            'utr' => 'UTRACME123456',
        ]);
    }

    public function test_partner_only_sees_own_developers(): void
    {
        $own = User::factory()->forWhitelabel($this->whitelabel->id)->create(['name' => 'Own Dev']);
        $own->assignRole(Role::Developer->value);

        $otherWl = Whitelabel::factory()->create();
        $other = User::factory()->forWhitelabel($otherWl->id)->create(['name' => 'Other Dev']);
        $other->assignRole(Role::Developer->value);

        Livewire::actingAs($this->partner)
            ->test(ListDevelopers::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_partner_only_sees_own_kyc_and_can_approve(): void
    {
        $applicant = User::factory()->forWhitelabel($this->whitelabel->id)->create([
            'name' => 'WL Applicant',
            'onboarding_status' => OnboardingStatus::KycSubmitted,
            'kyc_submitted_at' => now(),
        ]);
        $applicant->assignRole(Role::Developer->value);

        $otherWl = Whitelabel::factory()->create();
        $foreign = User::factory()->forWhitelabel($otherWl->id)->create([
            'name' => 'Foreign Applicant',
            'onboarding_status' => OnboardingStatus::KycSubmitted,
            'kyc_submitted_at' => now(),
        ]);
        $foreign->assignRole(Role::Developer->value);

        Livewire::actingAs($this->partner)
            ->test(ListKycApplications::class)
            ->assertCanSeeTableRecords([$applicant])
            ->assertCanNotSeeTableRecords([$foreign]);

        $this->actingAs($this->partner)
            ->get($this->partnerUrl('/partner/kyc-applications/'.$applicant->id))
            ->assertOk();

        app(\App\Actions\Onboarding\ApproveDeveloper::class)->handle($applicant, $this->partner);

        $this->assertSame(OnboardingStatus::Approved, $applicant->fresh()->onboarding_status);
        $this->assertSame($this->partner->id, $applicant->fresh()->approved_by);
    }

    public function test_partner_cannot_open_foreign_kyc(): void
    {
        $otherWl = Whitelabel::factory()->create();
        $foreign = User::factory()->forWhitelabel($otherWl->id)->create([
            'onboarding_status' => OnboardingStatus::KycSubmitted,
            'kyc_submitted_at' => now(),
        ]);
        $foreign->assignRole(Role::Developer->value);

        $this->actingAs($this->partner)
            ->get($this->partnerUrl('/partner/kyc-applications/'.$foreign->id))
            ->assertNotFound();
    }

    public function test_partner_can_view_own_recharge_rates(): void
    {
        WhitelabelOperatorCommission::query()->create([
            'whitelabel_id' => $this->whitelabel->id,
            'operator_type' => 'mobile',
            'operator_sp_key' => 116,
            'commission_percentage' => 2.25,
            'status' => true,
        ]);

        $this->actingAs($this->partner)
            ->get($this->partnerUrl('/partner/my-recharge-rates'))
            ->assertOk();

        Livewire::actingAs($this->partner)
            ->test(MyRechargeRates::class)
            ->assertSet('rows.mobile_116.commission_percentage', '2.25');
    }

    public function test_partner_can_save_developer_recharge_commission_within_cap(): void
    {
        WhitelabelOperatorCommission::query()->create([
            'whitelabel_id' => $this->whitelabel->id,
            'operator_type' => 'mobile',
            'operator_sp_key' => 116,
            'commission_percentage' => 2.50,
            'status' => true,
        ]);

        $developer = User::factory()->forWhitelabel($this->whitelabel->id)->create([
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        $component = Livewire::actingAs($this->partner)
            ->test(ManageDeveloperCommissions::class)
            ->set('selectedUserId', $developer->id);

        $instance = $component->instance();
        $instance->rows['mobile_116']['commission_percentage'] = '2.00';
        $instance->rows['mobile_116']['status'] = 'Active';
        $instance->save();

        $this->assertDatabaseHas('user_operator_commissions', [
            'user_id' => $developer->id,
            'operator_type' => 'mobile',
            'operator_sp_key' => 116,
            'commission_percentage' => 2.00,
        ]);
    }

    public function test_partner_cannot_set_developer_recharge_above_wl_rate(): void
    {
        WhitelabelOperatorCommission::query()->create([
            'whitelabel_id' => $this->whitelabel->id,
            'operator_type' => 'mobile',
            'operator_sp_key' => 116,
            'commission_percentage' => 2.00,
            'status' => true,
        ]);

        $developer = User::factory()->forWhitelabel($this->whitelabel->id)->create([
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        $component = Livewire::actingAs($this->partner)
            ->test(ManageDeveloperCommissions::class)
            ->set('selectedUserId', $developer->id);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $instance = $component->instance();
        $instance->rows['mobile_116']['commission_percentage'] = '5.00';
        $instance->save();
    }

    public function test_partner_cannot_set_foreign_developer_commissions(): void
    {
        $otherWl = Whitelabel::factory()->create();
        $foreign = User::factory()->forWhitelabel($otherWl->id)->create([
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $foreign->assignRole(Role::Developer->value);

        Livewire::actingAs($this->partner)
            ->test(ManageDeveloperCommissions::class)
            ->set('selectedUserId', $foreign->id)
            ->call('save')
            ->assertHasErrors(['selectedUserId']);
    }

    public function test_partner_can_save_developer_bill_commission_with_wl_cap(): void
    {
        WhitelabelBillOperatorCommission::query()->create([
            'whitelabel_id' => $this->whitelabel->id,
            'opcode' => 'CC01',
            'commission_type' => WhitelabelBillOperatorCommission::TYPE_PERCENTAGE,
            'commission_value' => 2.00,
            'status' => true,
        ]);

        $developer = User::factory()->forWhitelabel($this->whitelabel->id)->create([
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        $component = Livewire::actingAs($this->partner)
            ->test(PartnerInspayOperators::class)
            ->set('selectedUserId', $developer->id);

        $instance = $component->instance();
        $instance->commissionRows = [
            'CC01' => [
                'commission_type' => 'percentage',
                'commission_value' => '1.50',
                'status' => 'Active',
            ],
        ];
        $instance->saveCommissions();

        $this->assertDatabaseHas('user_bill_operator_commissions', [
            'user_id' => $developer->id,
            'opcode' => 'CC01',
            'commission_type' => 'percentage',
            'commission_value' => 1.50,
        ]);
    }

    public function test_partner_cannot_exceed_wl_bill_cap(): void
    {
        WhitelabelBillOperatorCommission::query()->create([
            'whitelabel_id' => $this->whitelabel->id,
            'opcode' => 'CC01',
            'commission_type' => WhitelabelBillOperatorCommission::TYPE_PERCENTAGE,
            'commission_value' => 1.00,
            'status' => true,
        ]);

        $developer = User::factory()->forWhitelabel($this->whitelabel->id)->create([
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        $component = Livewire::actingAs($this->partner)
            ->test(PartnerInspayOperators::class)
            ->set('selectedUserId', $developer->id);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $instance = $component->instance();
        $instance->commissionRows = [
            'CC01' => [
                'commission_type' => 'percentage',
                'commission_value' => '3.00',
                'status' => 'Active',
            ],
        ];
        $instance->saveCommissions();
    }

    public function test_partner_can_view_own_plan_api_rates(): void
    {
        WhitelabelPlanApiAccess::query()->create([
            'whitelabel_id' => $this->whitelabel->id,
            'service' => 'operator_fetch',
            'per_call_fee' => 0.10,
            'status' => true,
        ]);

        $this->actingAs($this->partner)
            ->get($this->partnerUrl('/partner/my-plan-api-rates'))
            ->assertOk();

        Livewire::actingAs($this->partner)
            ->test(MyPlanApiRates::class)
            ->assertSet('rows.operator_fetch.per_call_fee', '0.10')
            ->assertSet('rows.operator_fetch.status', 'Active');
    }

    public function test_partner_can_save_developer_plan_api_above_wl_cost(): void
    {
        WhitelabelPlanApiAccess::query()->create([
            'whitelabel_id' => $this->whitelabel->id,
            'service' => 'operator_fetch',
            'per_call_fee' => 0.10,
            'status' => true,
        ]);

        $developer = User::factory()->forWhitelabel($this->whitelabel->id)->create([
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        $component = Livewire::actingAs($this->partner)
            ->test(ManageDeveloperPlanApiAccess::class)
            ->set('selectedUserId', $developer->id);

        $instance = $component->instance();
        $instance->rows['operator_fetch']['per_call_fee'] = '0.18';
        $instance->rows['operator_fetch']['status'] = 'Active';
        $instance->save();

        $this->assertDatabaseHas('user_plan_api_access', [
            'user_id' => $developer->id,
            'service' => 'operator_fetch',
            'per_call_fee' => 0.18,
            'status' => 1,
        ]);
    }

    public function test_partner_cannot_set_developer_plan_api_below_wl_cost(): void
    {
        WhitelabelPlanApiAccess::query()->create([
            'whitelabel_id' => $this->whitelabel->id,
            'service' => 'operator_fetch',
            'per_call_fee' => 0.10,
            'status' => true,
        ]);

        $developer = User::factory()->forWhitelabel($this->whitelabel->id)->create([
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        $component = Livewire::actingAs($this->partner)
            ->test(ManageDeveloperPlanApiAccess::class)
            ->set('selectedUserId', $developer->id);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $instance = $component->instance();
        $instance->rows['operator_fetch']['per_call_fee'] = '0.05';
        $instance->rows['operator_fetch']['status'] = 'Active';
        $instance->save();
    }
}
