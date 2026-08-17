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

        $this->seedCreateLeadProfile($version, $group, $environments);
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
    private function seedCreateLeadProfile(ApiVersion $version, ApiGroup $group, $environments): void
    {
        $exampleRequest = [
            'first_name' => 'Rajesh',
            'last_name' => 'Jha',
            'mobile_no' => '9110409809',
            'email' => 'rajesh@example.com',
            'dob' => '1990-12-10',
            'company' => 75,
            'occupation' => 1,
            'monthly_salary' => 50000,
            'itr_amount' => 0,
            'gender' => 'Male',
            'pincode' => 560001,
            'address' => 'MG Road, Bengaluru',
            'category' => 'Individual',
            'category_id' => 3,
            'pan' => 'ABCDE1234F',
        ];

        $exampleResponse = [
            'status' => 'success',
            'message' => 'Customer profile has been created.',
            'data' => [
                'customer_id' => 'aUczK1BLZm1lRmtSNEZ6SGJTaHl0QT09',
                'mobile_no' => '9110409809',
                'category_id' => '3',
                'product_id' => null,
            ],
            'fee' => 0,
            'wallet_balance' => 1000.00,
        ];

        $endpoint = ApiEndpoint::updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => 'create-lead-profile',
            ],
            [
                'api_group_id' => $group->id,
                'name' => 'Create / Update Customer Profile',
                'method' => HttpMethod::Post,
                'path' => '/api/v1/leads/profile',
                'summary' => 'Create or update a customer profile and get customer_id',
                'description_md' => <<<'MD'
Creates a customer profile with the lead provider. If the mobile already exists, the same call **updates** the profile. Returns an encrypted `customer_id` — pass that into **Create Lead**.

**New vs existing**
- **New customer:** omit `customer_id`. `mobile_no` is required.
- **Existing customer:** send `customer_id` from a previous profile call. The same fields are still required so the provider can update the record.

**Occupation rule**
- Occupation id `2` (self-employed): send `itr_amount`; `monthly_salary` is sent as `0`.
- Any other occupation: send `monthly_salary`; `itr_amount` is sent as `0`.

**UAT vs Production**
- **UAT** tokens return a **sample** `customer_id`. No provider call, **fee = 0**.
- **Production** tokens create or update a live profile. **Not billed** (`fee = 0`).

**Access**
- Admin must enable **Lead generation** (`lead_generation`).
- Provider credentials (API key, IV) are applied server-side.

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
                'description' => 'Customer profile request body',
                'required' => true,
                'example' => $exampleRequest,
                'schema' => [
                    'type' => 'object',
                    'required' => [
                        'first_name',
                        'last_name',
                        'mobile_no',
                        'email',
                        'dob',
                        'company',
                        'occupation',
                        'monthly_salary',
                        'itr_amount',
                        'gender',
                        'pincode',
                        'address',
                        'category',
                        'category_id',
                        'pan',
                    ],
                    'properties' => [
                        'first_name' => ['type' => 'string', 'description' => 'First name (max 50)'],
                        'last_name' => ['type' => 'string', 'description' => 'Last name (max 50)'],
                        'mobile_no' => ['type' => 'string', 'description' => '10-digit Indian mobile number'],
                        'email' => ['type' => 'string', 'description' => 'Customer email'],
                        'dob' => ['type' => 'string', 'description' => 'Date of birth YYYY-MM-DD'],
                        'company' => ['type' => 'integer', 'description' => 'Company id from the provider company list'],
                        'occupation' => ['type' => 'integer', 'description' => 'Occupation id. Use 2 for self-employed (ITR instead of salary)'],
                        'monthly_salary' => ['type' => 'number', 'description' => 'Monthly salary in INR. Send 0 when occupation is 2'],
                        'itr_amount' => ['type' => 'number', 'description' => 'ITR amount in INR. Required when occupation is 2; otherwise send 0'],
                        'gender' => ['type' => 'string', 'description' => 'Male, Female, or Other'],
                        'pincode' => ['type' => 'integer', 'description' => 'Pincode id from the provider pincode list'],
                        'address' => ['type' => 'string', 'description' => 'Address (max 255)'],
                        'category' => ['type' => 'string', 'description' => 'Individual or Non-Individual'],
                        'category_id' => ['type' => 'integer', 'description' => 'Product category id'],
                        'pan' => ['type' => 'string', 'description' => 'PAN (10 characters, e.g. ABCDE1234F)'],
                        'customer_id' => ['type' => 'string', 'description' => 'Required only when updating an existing customer'],
                    ],
                ],
                'sort_order' => 1,
            ]
        );

        $this->seedResponseAndSamples($endpoint, $environments, $exampleRequest, $exampleResponse, 'Create / Update Customer Profile', true);
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
            'customer_id' => 'aUczK1BLZm1lRmtSNEZ6SGJTaHl0QT09',
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

Call **Create / Update Customer Profile** first and pass the returned `customer_id` here. If `customer_id` is omitted, the portal default customer id from Settings is used.

**UAT vs Production**
- **UAT** tokens return a **sample** response. No provider call, **fee = 0**.
- **Production** tokens create a live lead. **Create Lead is not billed** (`fee = 0`).

**Access & billing (Production)**
- Admin must enable **Lead generation** (`lead_generation`) and set the per-lead fee.
- Categories, products, details, profile, and create lead are included (`fee = 0`).
- The per-lead fee is charged on **Lead Status** the first time the status becomes `approved`.
- Provider credentials (API key, IV) are applied server-side.

`product_id` is required. `customer_id`, `category_id`, and `required_amount` are optional (`required_amount` is used for loans).

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
                        'customer_id' => ['type' => 'string', 'description' => 'Encrypted customer id from Create / Update Customer Profile'],
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
