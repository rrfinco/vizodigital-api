<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\User\Pages\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\WalletTransaction;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserPanelSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);
    }

    public function test_developer_can_view_subscription_plans_page(): void
    {
        SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Basic access',
            'price' => 499,
            'duration_days' => 30,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        SubscriptionPlan::create([
            'name' => 'Hidden Plan',
            'slug' => 'hidden-plan',
            'price' => 999,
            'duration_days' => 30,
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        $this->actingAs($user)
            ->get('/user/subscription')
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('499')
            ->assertDontSee('Hidden Plan');
    }

    public function test_developer_can_buy_plan_from_wallet(): void
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 250,
            'duration_days' => 30,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'wallet_balance' => 500.0000,
        ]);
        $user->assignRole(Role::Developer->value);

        Livewire::actingAs($user)
            ->test(Subscription::class)
            ->call('buyNow', $plan->id)
            ->assertNotified();

        $user->refresh();
        $this->assertEquals(250.0000, (float) $user->wallet_balance);

        $subscription = UserSubscription::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals($plan->id, $subscription->subscription_plan_id);
        $this->assertEquals(250.00, (float) $subscription->amount_paid);

        $txn = WalletTransaction::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($txn);
        $this->assertEquals('debit', $txn->type);
        $this->assertEquals(-250.0000, (float) $txn->amount);
    }

    public function test_buy_fails_when_wallet_balance_is_insufficient(): void
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price' => 1000,
            'duration_days' => 30,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'wallet_balance' => 100.0000,
        ]);
        $user->assignRole(Role::Developer->value);

        Livewire::actingAs($user)
            ->test(Subscription::class)
            ->call('buyNow', $plan->id)
            ->assertNotified();

        $user->refresh();
        $this->assertEquals(100.0000, (float) $user->wallet_balance);
        $this->assertDatabaseCount('user_subscriptions', 0);
    }
}
