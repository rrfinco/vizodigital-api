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

class LeadApiDocumentationSeeder extends Seeder
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

        $this->seedCreateLead($version, $group, $environments);
        $this->seedLeadStatus($version, $group, $environments);

        ApiGroup::query()
            ->where('api_category_id', $category->id)
            ->where('slug', 'applications')
            ->whereDoesntHave('endpoints')
            ->delete();
    }

    /**
     * @param  Collection<int, ApiEnvironment>  $environments
     */
    private function seedCreateLead(ApiVersion $version, ApiGroup $group, $environments): void
    {
        $exampleRequest = [
            'product_id' => '12345',
            'category_id' => 3,
            'required_amount' => 50000,
        ];

        $exampleResponse = [
            'status' => 'success',
            'message' => 'Lead created',
            'data' => [
                'lead_code' => 'BS-LEAD-987654',
                'campaign_url' => 'https://apply.example.test/campaign/xyz789',
            ],
            'fee' => 0,
            'wallet_balance' => 1000.00,
        ];

        $endpoint = ApiEndpoint::updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => 'create-lead',
            ],
            [
                'api_group_id' => $group->id,
                'name' => 'Create Lead',
                'method' => HttpMethod::Post,
                'path' => '/api/v1/leads',
                'summary' => 'Create a product application lead',
                'description_md' => <<<'MD'
Creates a lead for a product from **Products by Category**. Returns a `lead_code` and `campaign_url`.

**UAT vs Production**
- **UAT** tokens return a **sample** response. No provider call, **fee = 0**.
- **Production** tokens create a live lead. **Create Lead is not billed** (`fee = 0`).

**Access & billing (Production)**
- Admin must enable **Lead generation** (`lead_generation`) and set the per-lead fee.
- Categories, products, details, and create lead are included (`fee = 0`).
- The per-lead fee is charged on **Lead Status** the first time the status becomes `approved`.
- Provider credentials (including customer id) are applied server-side.

`product_id` is required. `category_id` and `required_amount` are optional (`required_amount` is used for loans).

Authenticate with a Bearer token.
MD,
                'status' => PublishStatus::Published,
                'access_service_key' => ProductApiService::SERVICE_LEAD_GENERATION,
                'rate_limit' => '60/min',
                'sort_order' => 4,
            ]
        );

        EndpointRequestBody::updateOrCreate(
            [
                'api_endpoint_id' => $endpoint->id,
                'content_type' => 'application/json',
            ],
            [
                'description' => 'Create lead request body',
                'required' => true,
                'example' => $exampleRequest,
                'schema' => [
                    'type' => 'object',
                    'required' => ['product_id'],
                    'properties' => [
                        'product_id' => ['type' => 'string', 'description' => 'Product id from Products by Category'],
                        'category_id' => ['type' => 'integer', 'description' => 'Optional category id'],
                        'required_amount' => ['type' => 'number', 'description' => 'Optional amount, e.g. for loans'],
                    ],
                ],
                'sort_order' => 1,
            ]
        );

        $this->seedResponseAndSamples($endpoint, $environments, $exampleRequest, $exampleResponse, 'Create Lead', true);
    }

    /**
     * @param  Collection<int, ApiEnvironment>  $environments
     */
    private function seedLeadStatus(ApiVersion $version, ApiGroup $group, $environments): void
    {
        $exampleResponse = [
            'status' => 'success',
            'message' => 'Lead status fetched',
            'data' => [
                'lead_code' => 'BS-LEAD-987654',
                'lead_status' => 'approved',
            ],
            'fee' => 0.10,
            'wallet_balance' => 999.90,
        ];

        $endpoint = ApiEndpoint::updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => 'lead-status',
            ],
            [
                'api_group_id' => $group->id,
                'name' => 'Lead Status',
                'method' => HttpMethod::Get,
                'path' => '/api/v1/leads/status',
                'summary' => 'Check the status of a lead',
                'description_md' => <<<'MD'
Returns the current status for a `lead_code` from **Create Lead**.

**Possible `lead_status` values**
- `pending`
- `submitted`
- `success`
- `approved`
- `failed`
- `rejected`

**Access & billing**
- Admin must enable **Lead generation** (`lead_generation`) and set the per-lead fee.
- `pending`, `submitted`, `failed`, and `rejected` are not billed (`fee = 0`).
- When `lead_status` is **new** compared with the last stored value **and** is `approved`, the per-lead fee is charged once.
- Hitting this API again while the status is still `approved` does **not** charge again.

Authenticate with a Bearer token.
MD,
                'status' => PublishStatus::Published,
                'access_service_key' => ProductApiService::SERVICE_LEAD_GENERATION,
                'rate_limit' => '60/min',
                'sort_order' => 5,
            ]
        );

        EndpointParameter::updateOrCreate(
            [
                'api_endpoint_id' => $endpoint->id,
                'location' => ParameterLocation::Query,
                'name' => 'lead_code',
            ],
            [
                'type' => 'string',
                'required' => true,
                'description' => 'Lead code returned by Create Lead.',
                'example' => 'BS-LEAD-987654',
                'sort_order' => 1,
            ]
        );

        $this->seedResponseAndSamples(
            $endpoint,
            $environments,
            ['lead_code' => 'BS-LEAD-987654'],
            $exampleResponse,
            'Lead Status',
            false
        );
    }

    /**
     * @param  Collection<int, ApiEnvironment>  $environments
     * @param  array<string, mixed>  $exampleRequest
     * @param  array<string, mixed>  $exampleResponse
     */
    private function seedResponseAndSamples(
        ApiEndpoint $endpoint,
        $environments,
        array $exampleRequest,
        array $exampleResponse,
        string $label,
        bool $isPost
    ): void {
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
                    'description' => 'Successful '.$label.' call.',
                    'sort_order' => 1,
                ]
            );

            $baseUrl = rtrim((string) $env->base_url, '/');

            if ($isPost) {
                $curl = <<<BASH
curl -X POST "{$baseUrl}{$endpoint->path}" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Content-Type: application/json" \\
  -H "Accept: application/json" \\
  -d '{$jsonBody}'
BASH;
            } else {
                $curl = <<<BASH
curl -X GET "{$baseUrl}{$endpoint->path}?lead_code=BS-LEAD-987654" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Accept: application/json"
BASH;
            }

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
}
