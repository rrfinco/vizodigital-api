<?php

namespace Database\Seeders;

use App\Enums\HttpMethod;
use App\Enums\PublishStatus;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\CodeSample;
use App\Models\EndpointExample;
use App\Models\EndpointRequestBody;
use App\Models\EndpointResponse;
use App\Services\EkycHub\EkycHubCatalog;
use App\Services\PlanApi\PlanApiService;
use Illuminate\Database\Seeder;

class PlanApiDocumentationSeeder extends Seeder
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
                'slug' => 'plan-apis',
            ],
            [
                'name' => 'Plan & Operator',
                'description' => 'Mobile operator find, prepaid plan fetch, and DTH plan / customer info APIs.',
                'icon' => 'device-phone-mobile',
                'status' => PublishStatus::Published,
                'show_in_sidebar' => true,
                'sort_order' => 4,
            ]
        );

        $group = ApiGroup::updateOrCreate(
            [
                'api_category_id' => $category->id,
                'slug' => 'verification',
            ],
            [
                'name' => 'Operator & Plans',
                'description' => 'Lookup APIs for mobile operators, recharge plans, and DTH.',
                'status' => PublishStatus::Published,
                'sort_order' => 1,
            ]
        );

        $environments = ApiEnvironment::query()
            ->whereIn('slug', ['uat', 'production'])
            ->get();

        $opcodeMd = $this->opcodeMarkdown();
        $circleMd = $this->circleMarkdown();

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'operator-fetch',
            'name' => 'Mobile Operator Find',
            'path' => '/api/v1/plan/operator-fetch',
            'summary' => 'Detect which operator a mobile number belongs to',
            'access_service_key' => PlanApiService::SERVICE_OPERATOR_FETCH,
            'sort_order' => 1,
            'description_md' => <<<MD
Verifies which telecom operator a mobile number belongs to and returns circle details.

**UAT vs Production**
- **UAT** tokens (`environment: uat` via client-credentials) return a **sample** response. No aggregator call, **fee = 0**.
- **Production** tokens call the live aggregator. Fee is deducted from your wallet.

**Access & billing (Production)**
- Admin must enable `operator_fetch` for your account and set a per-call fee.
- Fee is deducted from your **developer wallet** before the provider call.
- On provider failure, the fee is refunded automatically.
- Provider credentials are applied server-side — never send EkycHub username/token from the client.

{$opcodeMd}

{$circleMd}
MD,
            'example_request' => [
                'mobile' => '9431023126',
                'orderid' => 'OPF_001',
            ],
            'required' => ['mobile', 'orderid'],
            'properties' => [
                'mobile' => ['type' => 'string', 'description' => '10-digit mobile number'],
                'orderid' => ['type' => 'string', 'description' => 'Unique client order / request ID'],
            ],
            'example_response' => [
                'status' => 'success',
                'message' => 'UAT sample — operator fetched. Use production credentials for live aggregator data.',
                'data' => [
                    'number' => '9431023xxx',
                    'company' => 'Jio',
                    'circle' => 'Bihar',
                    'circle_code' => '52',
                    'orderid' => 'OPF_001',
                ],
                'fee' => 0,
                'wallet_balance' => 1000.00,
            ],
        ]);

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'operator-plan-fetch',
            'name' => 'Mobile Plan Fetch',
            'path' => '/api/v1/plan/operator-plan-fetch',
            'summary' => 'Fetch prepaid recharge plans for an operator and circle',
            'access_service_key' => PlanApiService::SERVICE_OPERATOR_PLAN_FETCH,
            'sort_order' => 2,
            'description_md' => <<<MD
Fetches available prepaid recharge plans (TOPUP, DATA, STV, etc.) for a mobile operator and circle.

Use **operator find** first to get `company` / `circle_code`, then map to the opcode and circle values below.

**UAT** returns sample TOPUP / DATA / STV packs (`fee = 0`, no aggregator hit). **Production** returns live plans and charges the configured fee.

**Access & billing** same as Operator Find (`operator_plan_fetch` service key).

{$opcodeMd}

{$circleMd}
MD,
            'example_request' => [
                'mobile' => '9431023126',
                'opcode' => 'J',
                'circle' => '52',
                'orderid' => 'PLN_001',
            ],
            'required' => ['mobile', 'opcode', 'circle', 'orderid'],
            'properties' => [
                'mobile' => ['type' => 'string', 'description' => '10-digit mobile number'],
                'opcode' => ['type' => 'string', 'description' => 'Operator code, e.g. A, V, J, BT, BS'],
                'circle' => ['type' => 'string', 'description' => 'Circle code, e.g. 52 for Bihar'],
                'orderid' => ['type' => 'string', 'description' => 'Unique client order / request ID'],
            ],
            'example_response' => [
                'status' => 'success',
                'message' => 'UAT sample — prepaid plans. Use production credentials for live aggregator data.',
                'data' => [
                    'operator' => 'Jio PREPAID',
                    'plans' => [
                        'TOPUP' => [
                            ['rs' => 10, 'validity' => 'NA', 'desc' => 'Sample talktime top-up (UAT)', 'Type' => 'talktime'],
                            ['rs' => 20, 'validity' => 'NA', 'desc' => 'Sample talktime top-up (UAT)', 'Type' => 'talktime'],
                        ],
                        'DATA' => [
                            ['rs' => 19, 'validity' => '1 Day', 'desc' => 'Sample 1GB data pack (UAT)', 'Type' => 'data'],
                            ['rs' => 299, 'validity' => '28 Days', 'desc' => 'Sample 2GB/day data pack (UAT)', 'Type' => 'data'],
                        ],
                        'STV' => [
                            ['rs' => 49, 'validity' => '28 Days', 'desc' => 'Sample unlimited calls STV (UAT)', 'Type' => 'stv'],
                        ],
                    ],
                    'orderid' => 'PLN_001',
                ],
                'fee' => 0,
                'wallet_balance' => 1000.00,
            ],
        ]);

        $dthOpcodes = collect(EkycHubCatalog::DTH_OPCODES)
            ->map(fn (string $name, string $code) => "- `{$code}` — {$name}")
            ->implode("\n");

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'dth-plan-fetch',
            'name' => 'DTH Plan Fetch',
            'path' => '/api/v1/plan/dth-plan-fetch',
            'summary' => 'Fetch DTH pack / plan information',
            'access_service_key' => PlanApiService::SERVICE_DTH_PLAN_FETCH,
            'sort_order' => 3,
            'description_md' => <<<MD
Fetches DTH plan packs for the given operator and subscriber number.

**UAT** returns sample Combo / AddOn packs (`fee = 0`). **Production** hits the live aggregator.

**Access & billing** same as Operator Find (`dth_plan_fetch` service key).

**DTH opcodes**
{$dthOpcodes}
MD,
            'example_request' => [
                'dth_number' => '07210298754',
                'opcode' => 'ATV',
                'orderid' => 'DTHP_001',
            ],
            'required' => ['dth_number', 'opcode', 'orderid'],
            'properties' => [
                'dth_number' => ['type' => 'string', 'description' => 'DTH subscriber / VC number'],
                'opcode' => ['type' => 'string', 'description' => 'DTH operator code, e.g. ATV'],
                'orderid' => ['type' => 'string', 'description' => 'Unique client order / request ID'],
            ],
            'example_response' => [
                'status' => 'success',
                'message' => 'UAT sample — DTH plans. Use production credentials for live aggregator data.',
                'data' => [
                    'operator' => 'Airtel DTH',
                    'plans' => [
                        'Combo' => [
                            ['rs' => 299, 'validity' => '30 Days', 'desc' => 'Sample combo pack (UAT)', 'Type' => 'combo'],
                            ['rs' => 499, 'validity' => '30 Days', 'desc' => 'Sample HD combo pack (UAT)', 'Type' => 'combo'],
                        ],
                        'AddOn' => [
                            ['rs' => 50, 'validity' => '30 Days', 'desc' => 'Sample sports add-on (UAT)', 'Type' => 'addon'],
                        ],
                    ],
                    'orderid' => 'DTHP_001',
                ],
                'fee' => 0,
                'wallet_balance' => 1000.00,
            ],
        ]);

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'dth-info',
            'name' => 'DTH Customer Info',
            'path' => '/api/v1/plan/dth-info',
            'summary' => 'Fetch DTH customer info and account balance',
            'access_service_key' => PlanApiService::SERVICE_DTH_INFO,
            'sort_order' => 4,
            'description_md' => <<<MD
Fetches DTH customer name, balance, and minimum recharge for the given operator and subscriber number.

**UAT** returns sample customer info (`fee = 0`). **Production** hits the live aggregator.

**Access & billing** same as Operator Find (`dth_info` service key).

**DTH opcodes**
{$dthOpcodes}
MD,
            'example_request' => [
                'dth_number' => '07210298754',
                'opcode' => 'ATV',
                'orderid' => 'DTHI_001',
            ],
            'required' => ['dth_number', 'opcode', 'orderid'],
            'properties' => [
                'dth_number' => ['type' => 'string', 'description' => 'DTH subscriber / VC number'],
                'opcode' => ['type' => 'string', 'description' => 'DTH operator code, e.g. ATV'],
                'orderid' => ['type' => 'string', 'description' => 'Unique client order / request ID'],
            ],
            'example_response' => [
                'status' => 'success',
                'message' => 'UAT sample — DTH customer info. Use production credentials for live aggregator data.',
                'data' => [
                    'customer' => [
                        [
                            'VC' => '07210298754',
                            'Name' => 'UAT Sample Customer',
                            'Balance' => '125.00',
                            'Minimum_recharge' => '200',
                        ],
                    ],
                    'orderid' => 'DTHI_001',
                ],
                'fee' => 0,
                'wallet_balance' => 1000.00,
            ],
        ]);

        if ($version->status === PublishStatus::Draft) {
            $version->forceFill(['status' => PublishStatus::Published])->save();
        }
    }

    private function opcodeMarkdown(): string
    {
        $lines = collect(EkycHubCatalog::MOBILE_OPCODES)
            ->map(fn (string $name, string $code) => "- `{$code}` — {$name}")
            ->implode("\n");

        return "**Mobile opcodes**\n{$lines}";
    }

    private function circleMarkdown(): string
    {
        $lines = collect(EkycHubCatalog::CIRCLE_CODES)
            ->map(fn (string $name, string $code) => "- `{$code}` — {$name}")
            ->implode("\n");

        return "**Circle codes**\n{$lines}";
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ApiEnvironment>  $environments
     * @param  array{
     *   slug: string,
     *   name: string,
     *   path: string,
     *   summary: string,
     *   sort_order: int,
     *   description_md: string,
     *   example_request: array<string, mixed>,
     *   required: list<string>,
     *   properties: array<string, array<string, mixed>>,
     *   example_response: array<string, mixed>,
     *   access_service_key?: string|null
     * }  $config
     */
    private function seedEndpoint(ApiVersion $version, ApiGroup $group, $environments, array $config): void
    {
        $endpoint = ApiEndpoint::updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => $config['slug'],
            ],
            [
                'api_group_id' => $group->id,
                'name' => $config['name'],
                'method' => HttpMethod::Post,
                'path' => $config['path'],
                'summary' => $config['summary'],
                'description_md' => $config['description_md'],
                'status' => PublishStatus::Published,
                'access_service_key' => $config['access_service_key'] ?? null,
                'rate_limit' => '60/min',
                'sort_order' => $config['sort_order'],
            ]
        );

        EndpointRequestBody::updateOrCreate(
            [
                'api_endpoint_id' => $endpoint->id,
                'content_type' => 'application/json',
            ],
            [
                'description' => $config['name'].' request body',
                'required' => true,
                'example' => $config['example_request'],
                'schema' => [
                    'type' => 'object',
                    'required' => $config['required'],
                    'properties' => $config['properties'],
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
                'example' => $config['example_response'],
                'sort_order' => 1,
            ]
        );

        $jsonBody = json_encode($config['example_request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        foreach ($environments as $env) {
            EndpointExample::updateOrCreate(
                [
                    'api_endpoint_id' => $endpoint->id,
                    'api_environment_id' => $env->id,
                    'title' => 'Success Response',
                ],
                [
                    'request' => $config['example_request'],
                    'response' => $config['example_response'],
                    'response_status' => 200,
                    'description' => 'Successful '.$config['name'].' call.',
                    'sort_order' => 1,
                ]
            );

            $baseUrl = rtrim((string) $env->base_url, '/');
            $curl = <<<BASH
curl -X POST "{$baseUrl}{$config['path']}" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Content-Type: application/json" \\
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
}
