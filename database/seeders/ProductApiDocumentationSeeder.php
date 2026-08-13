<?php

namespace Database\Seeders;

use App\Enums\HttpMethod;
use App\Enums\ParameterLocation;
use App\Enums\PublishStatus;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\CodeSample;
use App\Models\EndpointExample;
use App\Models\EndpointParameter;
use App\Models\EndpointRequestBody;
use App\Models\EndpointResponse;
use App\Services\ProductApi\ProductApiService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ProductApiDocumentationSeeder extends Seeder
{
    public function run(): void
    {
        $version = ApiVersion::query()->where('slug', 'v1')->first();

        if (! $version) {
            return;
        }

        $category = ApiCategory::updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => 'leads',
            ],
            [
                'name' => 'Leads',
                'description' => 'Lead generation: browse products, create leads, and track status.',
                'icon' => 'clipboard-document-list',
                'status' => PublishStatus::Published,
                'show_in_sidebar' => true,
                'sort_order' => 5,
            ]
        );

        $group = ApiGroup::updateOrCreate(
            [
                'api_category_id' => $category->id,
                'slug' => 'lead-generation',
            ],
            [
                'name' => 'Lead generation',
                'description' => 'Catalog, apply URL, create lead, and status — billed when a lead is approved.',
                'status' => PublishStatus::Published,
                'sort_order' => 1,
            ]
        );

        $environments = ApiEnvironment::query()
            ->whereIn('slug', ['uat', 'production'])
            ->get();

        $exampleResponse = [
            'status' => 'success',
            'message' => 'Product category fetched',
            'data' => [
                ['id' => 13, 'title' => 'Bank Accounts'],
                ['id' => 14, 'title' => 'Credit Cards'],
            ],
            'fee' => 0,
            'wallet_balance' => 1000.00,
        ];

        $endpoint = ApiEndpoint::updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => 'product-categories',
            ],
            [
                'api_group_id' => $group->id,
                'name' => 'Product Categories',
                'method' => HttpMethod::Get,
                'path' => '/api/v1/products/categories',
                'summary' => 'Retrieve all product categories',
                'description_md' => <<<'MD'
Returns every product category available to your account.

Use this before **Products by Category**.

**Access & billing**
- Admin must enable **Lead generation** (`lead_generation`) for your account.
- This call is included — **fee = 0**. The per-lead fee is charged on **Lead Status** when the status first becomes `approved`.
- Provider credentials are applied server-side.

No request body. Authenticate with a Bearer token.
MD,
                'status' => PublishStatus::Published,
                'access_service_key' => ProductApiService::SERVICE_LEAD_GENERATION,
                'rate_limit' => '60/min',
                'sort_order' => 1,
            ]
        );

        EndpointResponse::updateOrCreate(
            [
                'api_endpoint_id' => $endpoint->id,
                'status_code' => 200,
            ],
            [
                'description' => 'Successful response',
                'content_type' => 'application/json',
                'is_default' => true,
                'example' => $exampleResponse,
                'sort_order' => 1,
            ]
        );

        foreach ($environments as $env) {
            EndpointExample::updateOrCreate(
                [
                    'api_endpoint_id' => $endpoint->id,
                    'api_environment_id' => $env->id,
                    'title' => 'Success Response',
                ],
                [
                    'request' => [],
                    'response' => $exampleResponse,
                    'response_status' => 200,
                    'description' => 'Successful Product Categories call.',
                    'sort_order' => 1,
                ]
            );

            $baseUrl = rtrim((string) $env->base_url, '/');
            $curl = <<<BASH
curl -X GET "{$baseUrl}/api/v1/products/categories" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Accept: application/json"
BASH;

            CodeSample::updateOrCreate(
                [
                    'api_endpoint_id' => $endpoint->id,
                    'api_environment_id' => $env->id,
                    'language' => 'curl',
                ],
                [
                    'code' => $curl,
                    'is_generated' => false,
                    'is_override' => true,
                    'sort_order' => 1,
                ]
            );
        }

        $this->seedProductsByCategory($version, $group, $environments);
        $this->seedProductDetails($version, $group, $environments);

        $this->forgetCategory($version, 'products');

        ApiGroup::query()
            ->where('api_category_id', $category->id)
            ->where('slug', 'catalog')
            ->whereDoesntHave('endpoints')
            ->delete();
    }

    /**
     * @param  Collection<int, ApiEnvironment>  $environments
     */
    private function seedProductsByCategory(ApiVersion $version, ApiGroup $group, $environments): void
    {
        $exampleResponse = [
            'status' => 'success',
            'message' => 'Products fetched',
            'data' => [
                [
                    'product_id' => '12345',
                    'title' => 'HDFC Millennia Credit Card',
                    'sub_title' => '5% cashback on Amazon, Flipkart & more',
                    'logo' => 'https://cdn.example.test/products/hdfc-millennia.png',
                ],
                [
                    'product_id' => '12346',
                    'title' => 'SBI SimplyCLICK Credit Card',
                    'sub_title' => '10X rewards on online spends',
                    'logo' => 'https://cdn.example.test/products/sbi-simplyclick.png',
                ],
            ],
            'fee' => 0,
            'wallet_balance' => 1000.00,
        ];

        $endpoint = ApiEndpoint::updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => 'products-by-category',
            ],
            [
                'api_group_id' => $group->id,
                'name' => 'Products by Category',
                'method' => HttpMethod::Get,
                'path' => '/api/v1/products',
                'summary' => 'Retrieve products for a category',
                'description_md' => <<<'MD'
Returns products in the given category.

**Access & billing**
- Admin must enable **Lead generation** (`lead_generation`) for your account.
- This call is included — **fee = 0**. The per-lead fee is charged on **Lead Status** when the status first becomes `approved`.

**Common category IDs**
- `3` — Credit Cards
- `4` — Loans
- `13` — Bank Accounts
- `17` — Demat
- `30` — Credit Line

Use **Product Categories** first to get live `id` values, then pass `category_id` here.

Authenticate with a Bearer token.
MD,
                'status' => PublishStatus::Published,
                'access_service_key' => ProductApiService::SERVICE_LEAD_GENERATION,
                'rate_limit' => '60/min',
                'sort_order' => 2,
            ]
        );

        EndpointParameter::updateOrCreate(
            [
                'api_endpoint_id' => $endpoint->id,
                'location' => ParameterLocation::Query,
                'name' => 'category_id',
            ],
            [
                'type' => 'integer',
                'required' => true,
                'description' => 'Category ID from Product Categories, e.g. 3 for Credit Cards.',
                'example' => '3',
                'sort_order' => 1,
            ]
        );

        EndpointResponse::updateOrCreate(
            [
                'api_endpoint_id' => $endpoint->id,
                'status_code' => 200,
            ],
            [
                'description' => 'Successful response',
                'content_type' => 'application/json',
                'is_default' => true,
                'example' => $exampleResponse,
                'sort_order' => 1,
            ]
        );

        foreach ($environments as $env) {
            EndpointExample::updateOrCreate(
                [
                    'api_endpoint_id' => $endpoint->id,
                    'api_environment_id' => $env->id,
                    'title' => 'Success Response',
                ],
                [
                    'request' => ['category_id' => 3],
                    'response' => $exampleResponse,
                    'response_status' => 200,
                    'description' => 'Successful Products by Category call.',
                    'sort_order' => 1,
                ]
            );

            $baseUrl = rtrim((string) $env->base_url, '/');
            $curl = <<<BASH
curl -X GET "{$baseUrl}/api/v1/products?category_id=3" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Accept: application/json"
BASH;

            CodeSample::updateOrCreate(
                [
                    'api_endpoint_id' => $endpoint->id,
                    'api_environment_id' => $env->id,
                    'language' => 'curl',
                ],
                [
                    'code' => $curl,
                    'is_generated' => false,
                    'is_override' => true,
                    'sort_order' => 1,
                ]
            );
        }
    }

    /**
     * @param  Collection<int, ApiEnvironment>  $environments
     */
    private function seedProductDetails(ApiVersion $version, ApiGroup $group, $environments): void
    {
        $exampleRequest = [
            'product_id' => '12345',
            'category_id' => 3,
        ];

        $exampleResponse = [
            'status' => 'success',
            'message' => 'Product details fetched',
            'data' => [
                'url' => 'https://apply.example.test/campaign/abc123',
                'campaign_url' => 'https://apply.example.test/campaign/abc123',
            ],
            'fee' => 0,
            'wallet_balance' => 1000.00,
        ];

        $endpoint = ApiEndpoint::updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => 'product-details',
            ],
            [
                'api_group_id' => $group->id,
                'name' => 'Product Details',
                'method' => HttpMethod::Post,
                'path' => '/api/v1/products/details',
                'summary' => 'Fetch the apply / campaign URL for a product',
                'description_md' => <<<'MD'
Returns the apply URL for a product from **Products by Category**.

**Access & billing**
- Admin must enable **Lead generation** (`lead_generation`) for your account.
- This call is included — **fee = 0**. The per-lead fee is charged on **Lead Status** when the status first becomes `approved`.
- Provider credentials (including customer id) are applied server-side.

`product_id` is required. `category_id` is optional. `card_id` is optional (credit card cases).

Authenticate with a Bearer token.
MD,
                'status' => PublishStatus::Published,
                'access_service_key' => ProductApiService::SERVICE_LEAD_GENERATION,
                'rate_limit' => '60/min',
                'sort_order' => 3,
            ]
        );

        EndpointRequestBody::updateOrCreate(
            [
                'api_endpoint_id' => $endpoint->id,
                'content_type' => 'application/json',
            ],
            [
                'description' => 'Product details request body',
                'required' => true,
                'example' => $exampleRequest,
                'schema' => [
                    'type' => 'object',
                    'required' => ['product_id'],
                    'properties' => [
                        'product_id' => ['type' => 'string', 'description' => 'Product id from Products by Category'],
                        'category_id' => ['type' => 'integer', 'description' => 'Optional category id, e.g. 3 for Credit Cards'],
                        'card_id' => ['type' => 'integer', 'description' => 'Optional. Credit card cases only.'],
                    ],
                ],
                'sort_order' => 1,
            ]
        );

        EndpointResponse::updateOrCreate(
            [
                'api_endpoint_id' => $endpoint->id,
                'status_code' => 200,
            ],
            [
                'description' => 'Successful response',
                'content_type' => 'application/json',
                'is_default' => true,
                'example' => $exampleResponse,
                'sort_order' => 1,
            ]
        );

        $jsonBody = json_encode($exampleRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        foreach ($environments as $env) {
            EndpointExample::updateOrCreate(
                [
                    'api_endpoint_id' => $endpoint->id,
                    'api_environment_id' => $env->id,
                    'title' => 'Success Response',
                ],
                [
                    'request' => $exampleRequest,
                    'response' => $exampleResponse,
                    'response_status' => 200,
                    'description' => 'Successful Product Details call.',
                    'sort_order' => 1,
                ]
            );

            $baseUrl = rtrim((string) $env->base_url, '/');
            $curl = <<<BASH
curl -X POST "{$baseUrl}/api/v1/products/details" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Content-Type: application/json" \\
  -H "Accept: application/json" \\
  -d '{$jsonBody}'
BASH;

            CodeSample::updateOrCreate(
                [
                    'api_endpoint_id' => $endpoint->id,
                    'api_environment_id' => $env->id,
                    'language' => 'curl',
                ],
                [
                    'code' => $curl,
                    'is_generated' => false,
                    'is_override' => true,
                    'sort_order' => 1,
                ]
            );
        }
    }

    private function forgetCategory(ApiVersion $version, string $slug): void
    {
        $category = ApiCategory::query()
            ->where('api_version_id', $version->id)
            ->where('slug', $slug)
            ->first();

        if (! $category) {
            return;
        }

        foreach ($category->groups as $group) {
            if ($group->endpoints()->exists()) {
                continue;
            }

            $group->delete();
        }

        $category->load('groups');

        if ($category->groups->isEmpty()) {
            $category->delete();
        }
    }
}
