<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OnboardingStatus;
use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Models\WalletTransaction;
use App\Services\ProductApi\ProductApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => Role::Developer->value]);

        $this->user = User::factory()->create([
            'wallet_balance' => 100.0000,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $this->user->assignRole(Role::Developer->value);

        Setting::setValue('banksathi_base_url', 'https://tryleadapi.example.test', 'banksathi');
        Setting::setValue('banksathi_iv', 'TEST_IV', 'banksathi');
        Setting::setValue('banksathi_api_key', 'TEST_KEY', 'banksathi');
        Setting::setValue('banksathi_customer_id', 'TEST_CUSTOMER_ID', 'banksathi');
    }

    private function enableService(string $service, float $fee = 0.10, bool $active = true): void
    {
        UserPlanApiAccess::query()->updateOrCreate(
            [
                'user_id' => $this->user->id,
                'service' => $service,
            ],
            [
                'per_call_fee' => $fee,
                'status' => $active,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function providerSuccess(): array
    {
        return [
            'status' => true,
            'data' => [
                ['id' => 13, 'title' => 'Bank Accounts'],
            ],
            'message' => 'Product category fetched',
        ];
    }

    public function test_product_categories_requires_authentication(): void
    {
        $this->getJson(route('api.v1.products.categories'))
            ->assertUnauthorized();
    }

    public function test_product_categories_disabled_returns_403(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $this->getJson(route('api.v1.products.categories'))
            ->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'This API is not enabled for your account. Contact admin.');
    }

    public function test_product_categories_success_does_not_charge_and_sends_headers(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response($this->providerSuccess(), 200),
        ]);

        $this->getJson(route('api.v1.products.categories'))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Product category fetched')
            ->assertJsonPath('data.0.id', 13)
            ->assertJsonPath('data.0.title', 'Bank Accounts')
            ->assertJsonPath('fee', 0)
            ->assertJsonPath('wallet_balance', 100);

        $this->assertEquals(100.0, (float) $this->user->fresh()->wallet_balance);
        $this->assertSame(0, WalletTransaction::query()->count());

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://tryleadapi.example.test/api/b2b/allProductCategory'
                && $request->hasHeader('x-api-key', 'TEST_KEY')
                && $request->hasHeader('iv', 'TEST_IV');
        });
    }

    public function test_product_categories_strips_api_b2b_from_configured_base_url(): void
    {
        Setting::setValue('banksathi_base_url', 'https://tryleadapi.example.test/api/b2b', 'banksathi');
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response($this->providerSuccess(), 200),
        ]);

        $this->getJson(route('api.v1.products.categories'))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://tryleadapi.example.test/api/b2b/allProductCategory';
        });
        Http::assertNotSent(function ($request): bool {
            return str_contains($request->url(), '/api/b2b/api/b2b/');
        });
    }

    public function test_product_categories_provider_failure_does_not_touch_wallet(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response([
                'status' => false,
                'data' => [],
                'message' => 'Unable to fetch categories',
            ], 200),
        ]);

        $this->getJson(route('api.v1.products.categories'))
            ->assertStatus(400)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Unable to fetch categories');

        $this->assertEquals(100.0, (float) $this->user->fresh()->wallet_balance);
        $this->assertSame(0, WalletTransaction::query()->count());
    }

    public function test_whitelabel_catalog_does_not_charge_per_lead_fee(): void
    {
        $whitelabel = \App\Models\Whitelabel::factory()->withFloat(100)->create();
        $developer = User::factory()->forWhitelabel($whitelabel->id)->create([
            'wallet_balance' => 50,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        \App\Models\WhitelabelPlanApiAccess::query()->create([
            'whitelabel_id' => $whitelabel->id,
            'service' => ProductApiService::SERVICE_LEAD_GENERATION,
            'per_call_fee' => 0.10,
            'status' => true,
        ]);

        UserPlanApiAccess::query()->create([
            'user_id' => $developer->id,
            'service' => ProductApiService::SERVICE_LEAD_GENERATION,
            'per_call_fee' => 0.15,
            'status' => true,
        ]);

        $this->actingAs($developer, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response($this->providerSuccess(), 200),
        ]);

        $this->getJson(route('api.v1.products.categories'))
            ->assertOk()
            ->assertJsonPath('fee', 0)
            ->assertJsonPath('wallet_balance', 50);

        $this->assertSame(50.0, (float) $developer->fresh()->wallet_balance);
        $this->assertSame(100.0, (float) $whitelabel->fresh()->wallet_balance);
        $this->assertSame(0, \App\Models\WhitelabelWalletTransaction::query()->count());
    }

    public function test_whitelabel_inactive_wl_service_returns_503(): void
    {
        $whitelabel = \App\Models\Whitelabel::factory()->withFloat(100)->create();
        $developer = User::factory()->forWhitelabel($whitelabel->id)->create([
            'wallet_balance' => 50,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        UserPlanApiAccess::query()->create([
            'user_id' => $developer->id,
            'service' => ProductApiService::SERVICE_LEAD_GENERATION,
            'per_call_fee' => 0.15,
            'status' => true,
        ]);

        $this->actingAs($developer, 'sanctum');

        $this->getJson(route('api.v1.products.categories'))
            ->assertStatus(503)
            ->assertJsonPath('code', 'SERVICE_UNAVAILABLE');

        $this->assertSame(50.0, (float) $developer->fresh()->wallet_balance);
        $this->assertSame(100.0, (float) $whitelabel->fresh()->wallet_balance);
    }

    public function test_uat_token_returns_mock_categories_without_http_or_fee(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);

        $plain = $this->user->createToken('uat-test', ['*', 'environment:uat'])->plainTextToken;

        Http::fake();

        $this->withToken($plain)->getJson(route('api.v1.products.categories'))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.id', 13)
            ->assertJsonPath('data.0.title', 'Bank Accounts')
            ->assertJsonPath('data.1.title', 'Credit Cards')
            ->assertJsonPath('fee', 0)
            ->assertJsonPath('wallet_balance', 100);

        $this->assertEquals(100.0, (float) $this->user->fresh()->wallet_balance);
        $this->assertSame(0, WalletTransaction::query()->count());
        Http::assertNothingSent();
    }

    /**
     * @return array<string, mixed>
     */
    private function productsProviderSuccess(): array
    {
        return [
            'status' => true,
            'data' => [
                [
                    'product_id' => '12345',
                    'title' => 'HDFC Millennia Credit Card',
                    'sub_title' => '5% cashback on Amazon, Flipkart & more',
                    'logo' => 'https://cdn.example.test/products/hdfc-millennia.png',
                ],
            ],
            'message' => 'Products fetched',
        ];
    }

    public function test_products_by_category_requires_authentication(): void
    {
        $this->getJson(route('api.v1.products.index', ['category_id' => 3]))
            ->assertUnauthorized();
    }

    public function test_products_by_category_disabled_returns_403(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $this->getJson(route('api.v1.products.index', ['category_id' => 3]))
            ->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'This API is not enabled for your account. Contact admin.');
    }

    public function test_products_by_category_validation(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION);
        $this->actingAs($this->user, 'sanctum');

        $this->getJson(route('api.v1.products.index'))
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_products_by_category_success_does_not_charge_and_sends_query(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response($this->productsProviderSuccess(), 200),
        ]);

        $this->getJson(route('api.v1.products.index', ['category_id' => 3]))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Products fetched')
            ->assertJsonPath('data.0.product_id', '12345')
            ->assertJsonPath('data.0.title', 'HDFC Millennia Credit Card')
            ->assertJsonPath('data.0.sub_title', '5% cashback on Amazon, Flipkart & more')
            ->assertJsonPath('fee', 0);

        $this->assertEquals(100.0, (float) $this->user->fresh()->wallet_balance);

        Http::assertSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://tryleadapi.example.test/api/b2b/productByCategory')
                && $request['category_id'] == 3
                && $request->hasHeader('x-api-key', 'TEST_KEY')
                && $request->hasHeader('iv', 'TEST_IV');
        });
    }

    public function test_uat_token_returns_mock_products_without_http_or_fee(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);

        $plain = $this->user->createToken('uat-products-test', ['*', 'environment:uat'])->plainTextToken;

        Http::fake();

        $this->withToken($plain)->getJson(route('api.v1.products.index', ['category_id' => 3]))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.product_id', '12345')
            ->assertJsonPath('data.1.title', 'SBI SimplyCLICK Credit Card')
            ->assertJsonPath('fee', 0)
            ->assertJsonPath('wallet_balance', 100);

        $this->assertEquals(100.0, (float) $this->user->fresh()->wallet_balance);
        $this->assertSame(0, WalletTransaction::query()->count());
        Http::assertNothingSent();
    }

    public function test_product_details_requires_authentication(): void
    {
        $this->postJson(route('api.v1.products.details'), [
            'product_id' => '12345',
        ])->assertUnauthorized();
    }

    public function test_product_details_disabled_returns_403(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.products.details'), [
            'product_id' => '12345',
        ])
            ->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'This API is not enabled for your account. Contact admin.');
    }

    public function test_product_details_validation(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.products.details'), [])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_product_details_success_sends_customer_id_and_category_typo(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response([
                'status' => true,
                'data' => [
                    'url' => 'https://apply.example.test/campaign/abc123',
                    'campaign_url' => 'https://apply.example.test/campaign/abc123',
                ],
                'message' => 'Product details fetched',
            ], 200),
        ]);

        $this->postJson(route('api.v1.products.details'), [
            'product_id' => '12345',
            'category_id' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.url', 'https://apply.example.test/campaign/abc123')
            ->assertJsonPath('data.campaign_url', 'https://apply.example.test/campaign/abc123')
            ->assertJsonPath('fee', 0);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://tryleadapi.example.test/api/b2b/otherProductDetails'
                && $request['customer_id'] === 'TEST_CUSTOMER_ID'
                && $request['product_id'] === '12345'
                && $request['category_id'] == 3
                && $request['categroy_id'] == 3
                && $request->hasHeader('x-api-key', 'TEST_KEY')
                && $request->hasHeader('iv', 'TEST_IV');
        });
    }

    public function test_product_details_fills_url_from_campaign_url(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.0);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response([
                'status' => true,
                'data' => [
                    'campaign_url' => 'https://apply.example.test/campaign/only-campaign',
                ],
            ], 200),
        ]);

        $this->postJson(route('api.v1.products.details'), [
            'product_id' => '12345',
        ])
            ->assertOk()
            ->assertJsonPath('data.url', 'https://apply.example.test/campaign/only-campaign')
            ->assertJsonPath('data.campaign_url', 'https://apply.example.test/campaign/only-campaign');
    }

    public function test_uat_token_returns_mock_product_details_without_http_or_fee(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);

        $plain = $this->user->createToken('uat-details-test', ['*', 'environment:uat'])->plainTextToken;

        Http::fake();

        $this->withToken($plain)->postJson(route('api.v1.products.details'), [
            'product_id' => '12345',
            'category_id' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.url', 'https://apply.example.test/campaign/uat-12345')
            ->assertJsonPath('fee', 0);

        Http::assertNothingSent();
        $this->assertSame(0, WalletTransaction::query()->count());
    }
}
