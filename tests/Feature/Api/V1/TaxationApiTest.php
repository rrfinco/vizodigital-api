<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OnboardingStatus;
use App\Enums\Role;
use App\Models\TaxationCategory;
use App\Models\TaxationDocument;
use App\Models\TaxationOrder;
use App\Models\TaxationService;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Models\Whitelabel;
use App\Models\WhitelabelPlanApiAccess;
use App\Services\Taxation\TaxationApiService;
use App\Services\Taxation\TaxationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaxationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => Role::Developer->value]);

        $this->user = User::factory()->create([
            'wallet_balance' => 10000,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $this->user->assignRole(Role::Developer->value);

        $category = TaxationCategory::query()->create([
            'name' => 'GST',
            'slug' => 'gst',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        TaxationService::query()->create([
            'id' => 194,
            'taxation_category_id' => $category->id,
            'name' => 'GST REGISTRATION (REGULAR)',
            'price' => 700,
            'default_commission_percentage' => 2,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function enableTaxation(User $user, bool $active = true): void
    {
        UserPlanApiAccess::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'service' => TaxationCatalog::SERVICE_ACCESS_KEY,
            ],
            [
                'per_call_fee' => 0,
                'status' => $active,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function clientPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Aman',
            'middle_name' => 'Kumar',
            'last_name' => 'Raj',
            'email' => 'aman@example.com',
            'phone' => '9876543210',
            'pan' => 'ABCDE1234F',
            'aadhaar' => '123412341234',
            'residence_address' => '12 MG Road',
            'residence_city' => 'Patna',
            'residence_pincode' => '800001',
            'residence_state' => 'Bihar',
            'office_address' => '44 Exhibition Road',
            'office_city' => 'Patna',
            'office_pincode' => '800001',
            'office_state' => 'Bihar',
            'client_request_id' => 'CLIENT_001',
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson(route('api.v1.taxation.clients.store'), $this->clientPayload())
            ->assertStatus(401);
    }

    public function test_requires_access(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.v1.taxation.services'))
            ->assertStatus(403)
            ->assertJsonPath('message', 'This API is not enabled for your account. Contact admin.');
    }

    public function test_create_client_requires_all_fields(): void
    {
        $this->enableTaxation($this->user);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.taxation.clients.store'), [
            'first_name' => 'Aman',
        ])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_create_client_success(): void
    {
        $this->enableTaxation($this->user);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.taxation.clients.store'), $this->clientPayload())
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.pan', 'ABCDE1234F')
            ->assertJsonPath('data.phone', '9876543210');

        $this->assertDatabaseHas('taxation_clients', [
            'user_id' => $this->user->id,
            'client_request_id' => 'CLIENT_001',
            'pan' => 'ABCDE1234F',
        ]);
    }

    public function test_duplicate_client_request_id_rejected(): void
    {
        $this->enableTaxation($this->user);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.taxation.clients.store'), $this->clientPayload())->assertOk();

        $this->postJson(route('api.v1.taxation.clients.store'), $this->clientPayload([
            'email' => 'other@example.com',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.client_request_id.0', 'This client_request_id was already used. Provide a unique order ID.');
    }

    public function test_list_services_returns_catalog_ids(): void
    {
        $this->enableTaxation($this->user);
        $this->actingAs($this->user, 'sanctum');

        $this->getJson(route('api.v1.taxation.services'))
            ->assertOk()
            ->assertJsonPath('data.0.service_id', 194)
            ->assertJsonPath('data.0.price', 700);
    }

    public function test_create_order_debits_wallet_at_catalog_price(): void
    {
        $this->enableTaxation($this->user);
        $this->actingAs($this->user, 'sanctum');

        $clientId = $this->postJson(route('api.v1.taxation.clients.store'), $this->clientPayload())
            ->json('data.client_id');

        $this->postJson(route('api.v1.taxation.orders.store'), [
            'client_id' => $clientId,
            'service_id' => 194,
            'client_request_id' => 'ORDER_001',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.service_id', 194)
            ->assertJsonPath('data.amount', 700)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.documents_status', 'pending');

        $this->assertSame(9300.0, (float) $this->user->fresh()->wallet_balance);
        $this->assertDatabaseHas('taxation_orders', [
            'user_id' => $this->user->id,
            'taxation_service_id' => 194,
            'status' => TaxationOrder::STATUS_PENDING,
        ]);
    }

    public function test_create_order_insufficient_balance(): void
    {
        $this->user->update(['wallet_balance' => 10]);
        $this->enableTaxation($this->user);
        $this->actingAs($this->user, 'sanctum');

        $clientId = $this->postJson(route('api.v1.taxation.clients.store'), $this->clientPayload())
            ->json('data.client_id');

        $this->postJson(route('api.v1.taxation.orders.store'), [
            'client_id' => $clientId,
            'service_id' => 194,
        ])
            ->assertStatus(400)
            ->assertJsonPath('status', 'error');

        $this->assertSame(10.0, (float) $this->user->fresh()->wallet_balance);
        $this->assertDatabaseCount('taxation_orders', 0);
    }

    public function test_whitelabel_float_exhausted_returns_503(): void
    {
        $wl = Whitelabel::factory()->withFloat(10)->create();
        $developer = User::factory()->forWhitelabel($wl->id)->create([
            'wallet_balance' => 10000,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);
        $this->enableTaxation($developer);

        WhitelabelPlanApiAccess::query()->create([
            'whitelabel_id' => $wl->id,
            'service' => TaxationCatalog::SERVICE_ACCESS_KEY,
            'per_call_fee' => 0,
            'status' => true,
        ]);

        $this->actingAs($developer, 'sanctum');
        $clientId = $this->postJson(route('api.v1.taxation.clients.store'), $this->clientPayload())
            ->json('data.client_id');

        $this->postJson(route('api.v1.taxation.orders.store'), [
            'client_id' => $clientId,
            'service_id' => 194,
        ])
            ->assertStatus(503)
            ->assertJsonPath('code', 'SERVICE_UNAVAILABLE');

        $this->assertSame(10000.0, (float) $developer->fresh()->wallet_balance);
        $this->assertSame(10.0, (float) $wl->fresh()->wallet_balance);
        $this->assertDatabaseCount('taxation_orders', 0);
    }

    public function test_whitelabel_success_debits_developer_and_float(): void
    {
        $wl = Whitelabel::factory()->withFloat(5000)->create();
        $developer = User::factory()->forWhitelabel($wl->id)->create([
            'wallet_balance' => 10000,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);
        $this->enableTaxation($developer);

        WhitelabelPlanApiAccess::query()->create([
            'whitelabel_id' => $wl->id,
            'service' => TaxationCatalog::SERVICE_ACCESS_KEY,
            'per_call_fee' => 0,
            'status' => true,
        ]);

        $this->actingAs($developer, 'sanctum');
        $clientId = $this->postJson(route('api.v1.taxation.clients.store'), $this->clientPayload())
            ->json('data.client_id');

        $this->postJson(route('api.v1.taxation.orders.store'), [
            'client_id' => $clientId,
            'service_id' => 194,
        ])->assertOk();

        $this->assertSame(9300.0, (float) $developer->fresh()->wallet_balance);
        $this->assertSame(4300.0, (float) $wl->fresh()->wallet_balance);
    }

    public function test_order_rejects_unknown_client(): void
    {
        $this->enableTaxation($this->user);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.taxation.orders.store'), [
            'client_id' => 999,
            'service_id' => 194,
        ])
            ->assertStatus(422);
    }

    public function test_catalog_service_ids_are_unique(): void
    {
        $ids = array_column(TaxationCatalog::services(), 'id');

        $this->assertCount(count($ids), array_unique($ids));
        $this->assertContains(1, $ids);
        $this->assertContains(194, $ids);
        $this->assertContains(262, $ids);
    }

    public function test_demo_merchant_uploads_documents_after_payment_and_admin_verifies_and_approves(): void
    {
        Storage::fake('local');

        $merchant = User::factory()->create([
            'name' => 'Portal User',
            'email' => 'user@portal.test',
            'company_name' => 'Demo Merchant',
            'wallet_balance' => 10000,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $merchant->assignRole(Role::Developer->value);
        $this->enableTaxation($merchant);

        $admin = User::factory()->create([
            'name' => 'Portal Admin',
            'email' => 'admin@portal.test',
        ]);

        $this->actingAs($merchant, 'sanctum');

        $clientId = $this->postJson(route('api.v1.taxation.clients.store'), $this->clientPayload([
            'client_request_id' => 'DEMO_CLIENT_001',
        ]))->json('data.client_id');

        $orderId = $this->postJson(route('api.v1.taxation.orders.store'), [
            'client_id' => $clientId,
            'service_id' => 194,
            'client_request_id' => 'DEMO_ORDER_001',
        ])
            ->assertOk()
            ->json('data.order_id');

        $this->post(route('api.v1.taxation.orders.documents.store', ['order' => $orderId]), [
            'documents' => [
                [
                    'type' => 'pan_card',
                    'file' => UploadedFile::fake()->create('demo-pan.pdf', 80, 'application/pdf'),
                ],
                [
                    'type' => 'aadhaar_card',
                    'file' => UploadedFile::fake()->create('demo-aadhaar.pdf', 90, 'application/pdf'),
                ],
            ],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.documents_status', 'submitted')
            ->assertJsonPath('data.documents.0.type', 'pan_card')
            ->assertJsonPath('data.documents.0.status', 'pending');

        $this->assertDatabaseHas('taxation_orders', [
            'id' => $orderId,
            'user_id' => $merchant->id,
            'status' => TaxationOrder::STATUS_PROCESSING,
            'documents_status' => TaxationOrder::DOCUMENTS_SUBMITTED,
        ]);
        $this->assertDatabaseCount('taxation_documents', 2);

        $order = TaxationOrder::query()->findOrFail($orderId);
        $service = app(TaxationApiService::class);

        $service->markDocumentsVerified($order, $admin);
        $order->refresh();
        $this->assertSame(TaxationOrder::DOCUMENTS_VERIFIED, $order->documents_status);
        $this->assertSame($admin->id, $order->documents_reviewed_by);
        $this->assertTrue(
            $order->documents()->where('status', TaxationDocument::STATUS_VERIFIED)->count() === 2
        );

        $service->approveDocuments($order, $admin);
        $order->refresh();
        $this->assertSame(TaxationOrder::DOCUMENTS_APPROVED, $order->documents_status);
        $this->assertSame(TaxationOrder::STATUS_COMPLETED, $order->status);

        $this->getJson(route('api.v1.taxation.orders.documents.index', ['order' => $orderId]))
            ->assertOk()
            ->assertJsonPath('data.documents_status', 'approved')
            ->assertJsonPath('data.documents.0.status', 'verified');
    }

    public function test_cannot_upload_documents_before_order_exists(): void
    {
        Storage::fake('local');
        $this->enableTaxation($this->user);
        $this->actingAs($this->user, 'sanctum');

        $this->post(route('api.v1.taxation.orders.documents.store', ['order' => 999]), [
            'documents' => [
                [
                    'type' => 'pan_card',
                    'file' => UploadedFile::fake()->create('pan.pdf', 40, 'application/pdf'),
                ],
            ],
        ], ['Accept' => 'application/json'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Order not found.');
    }

    public function test_admin_cannot_approve_without_documents(): void
    {
        $this->enableTaxation($this->user);
        $this->actingAs($this->user, 'sanctum');

        $clientId = $this->postJson(route('api.v1.taxation.clients.store'), $this->clientPayload())
            ->json('data.client_id');

        $orderId = $this->postJson(route('api.v1.taxation.orders.store'), [
            'client_id' => $clientId,
            'service_id' => 194,
        ])->json('data.order_id');

        $order = TaxationOrder::query()->findOrFail($orderId);
        $admin = User::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No documents have been uploaded yet.');

        app(TaxationApiService::class)->approveDocuments($order, $admin);
    }

    public function test_rejected_documents_can_be_replaced(): void
    {
        Storage::fake('local');
        $this->enableTaxation($this->user);
        $this->actingAs($this->user, 'sanctum');

        $clientId = $this->postJson(route('api.v1.taxation.clients.store'), $this->clientPayload())
            ->json('data.client_id');
        $orderId = $this->postJson(route('api.v1.taxation.orders.store'), [
            'client_id' => $clientId,
            'service_id' => 194,
        ])->json('data.order_id');

        $this->post(route('api.v1.taxation.orders.documents.store', ['order' => $orderId]), [
            'documents' => [
                [
                    'type' => 'pan_card',
                    'file' => UploadedFile::fake()->create('blurry-pan.pdf', 40, 'application/pdf'),
                ],
            ],
        ], ['Accept' => 'application/json'])->assertOk();

        $order = TaxationOrder::query()->findOrFail($orderId);
        $admin = User::factory()->create();
        $service = app(TaxationApiService::class);

        $service->rejectDocuments($order, $admin, 'PAN image is not readable.');
        $order->refresh();
        $this->assertSame(TaxationOrder::DOCUMENTS_REJECTED, $order->documents_status);

        $this->post(route('api.v1.taxation.orders.documents.store', ['order' => $orderId]), [
            'documents' => [
                [
                    'type' => 'pan_card',
                    'file' => UploadedFile::fake()->create('clear-pan.pdf', 50, 'application/pdf'),
                ],
            ],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.documents_status', 'submitted');

        $order->refresh();
        $service->approveDocuments($order, $admin);
        $this->assertSame(TaxationOrder::DOCUMENTS_APPROVED, $order->fresh()->documents_status);
    }
}
