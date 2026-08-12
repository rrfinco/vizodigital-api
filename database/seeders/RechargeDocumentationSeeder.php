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
use Illuminate\Database\Seeder;

class RechargeDocumentationSeeder extends Seeder
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
                'slug' => 'recharge',
            ],
            [
                'name' => 'Recharge',
                'description' => 'Process mobile and DTH recharge transactions from your wallet balance.',
                'icon' => 'device-phone-mobile',
                'status' => PublishStatus::Published,
                'show_in_sidebar' => true,
                'sort_order' => 1,
            ]
        );

        $group = ApiGroup::updateOrCreate(
            [
                'api_category_id' => $category->id,
                'slug' => 'recharge-services',
            ],
            [
                'name' => 'Recharge Services',
                'description' => 'Mobile and DTH recharge APIs.',
                'status' => PublishStatus::Published,
                'sort_order' => 1,
            ]
        );

        $environments = ApiEnvironment::query()
            ->whereIn('slug', ['uat', 'production'])
            ->get();

        // Remove the old combined "Recharge API" doc — Mobile + DTH are the only public entries.
        $this->deleteDuplicateCombinedEndpoint($version);

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'mobile-recharge',
            'name' => 'Mobile Recharge',
            'summary' => 'Process prepaid mobile recharge. Ensure client_request_id is unique per request.',
            'operator_type' => 'mobile',
            'account_number' => '9876543210',
            'sort_order' => 1,
        ]);

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'dth-recharge',
            'name' => 'DTH Recharge',
            'summary' => 'Process prepaid DTH recharge. Ensure client_request_id is unique per request.',
            'operator_type' => 'dth',
            'account_number' => '1234567890',
            'sort_order' => 2,
        ]);

        ApiCategory::query()
            ->where('api_version_id', $version->id)
            ->where('slug', 'bill-payment')
            ->update(['sort_order' => 2]);

        if ($version->status === PublishStatus::Draft) {
            $version->forceFill(['status' => PublishStatus::Published])->save();
        }
    }

    private function deleteDuplicateCombinedEndpoint(ApiVersion $version): void
    {
        $duplicate = ApiEndpoint::query()
            ->where('api_version_id', $version->id)
            ->where('slug', 'recharge')
            ->first();

        if (! $duplicate) {
            return;
        }

        $duplicate->requestBodies()->delete();
        $duplicate->responses()->delete();
        $duplicate->examples()->delete();
        $duplicate->codeSamples()->delete();
        $duplicate->sections()->delete();
        $duplicate->parameters()->delete();
        $duplicate->headers()->delete();
        $duplicate->errors()->delete();
        $duplicate->notes()->delete();
        $duplicate->relatedEndpoints()->detach();
        $duplicate->delete();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ApiEnvironment>  $environments
     * @param  array{slug: string, name: string, summary: string, operator_type: string, account_number: string, sort_order: int}  $config
     */
    private function seedEndpoint(ApiVersion $version, ApiGroup $group, $environments, array $config): void
    {
        $operatorType = $config['operator_type'];
        $accountNumber = $config['account_number'];
        $label = $config['name'];
        $isMobile = $operatorType === 'mobile';
        $requestExample = $isMobile
            ? [
                'account_number' => '9431023126',
                'amount' => 10,
                'operator_sp_key' => 116,
                'operator_type' => 'mobile',
                'circle' => 'Bihar and Jharkhand',
                'client_request_id' => '#Vizo28',
            ]
            : [
                'account_number' => $accountNumber,
                'amount' => 10,
                'operator_sp_key' => 51,
                'operator_type' => 'dth',
                'client_request_id' => 'UNIQUE_ID_123',
            ];
        $providerNotes = $isMobile
            ? "- If your account is assigned **Roundpay**, the existing recharge payload keeps working and `circle` stays optional.\n- If your account is assigned **Mokshiq**, pass `operator_sp_key` and `circle` from operator/plan fetch. Example: `operator_sp_key: 116` (`Jio`) with `circle: \"Bihar and Jharkhand\"`.\n- UAT and Production use the same request / response contract; only the base URL and credentials change."
            : "- UAT and Production use the same request / response contract for DTH recharge; only the base URL and credentials change.\n- `circle` is not required for DTH on either Roundpay or Mokshiq.";

        $endpoint = ApiEndpoint::updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => $config['slug'],
            ],
            [
                'api_group_id' => $group->id,
                'name' => $config['name'],
                'method' => HttpMethod::Post,
                'path' => '/api/v1/recharge',
                'summary' => $config['summary'],
                'description_md' => <<<MD
Submit a **{$label}** request against your developer wallet.

**Notes**
- Authenticate with `Authorization: Bearer {token}`.
- Set `operator_type` to `{$operatorType}`.
- Amount is deducted from wallet before the provider call; failed attempts are refunded.
- Use a unique `client_request_id` to avoid duplicate processing.
- Admin assigns one recharge provider per platform developer (or per white-label for WL developers): **Roundpay** or **Mokshiq**.
- For **Mokshiq** mobile recharges, first call operator/plan fetch, then pass `circle` (circle name from the fetch response is fine — the portal normalizes names like `Bihar and Jharkhand` → `Bihar Jharkhand` for Mokshiq).
- For Mokshiq **DTH** recharges, `circle` is not required.
{$providerNotes}
MD,
                'status' => PublishStatus::Published,
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
                'description' => "{$label} request body",
                'required' => true,
                'example' => $requestExample,
                'schema' => [
                    'type' => 'object',
                    'required' => ['account_number', 'amount', 'operator_sp_key', 'operator_type'],
                    'properties' => [
                        'account_number' => ['type' => 'string', 'description' => $operatorType === 'mobile' ? '10-digit mobile number' : 'DTH subscriber / account number'],
                        'amount' => ['type' => 'number', 'description' => 'Recharge amount in INR'],
                        'operator_sp_key' => ['type' => 'integer', 'description' => $isMobile ? 'Operator code / SP key. Example: `116` for Jio or `3` for Airtel.' : 'Operator code / SP key. Example: `51` for Airtel Digital TV.'],
                        'operator_type' => ['type' => 'string', 'enum' => [$operatorType]],
                        'client_request_id' => ['type' => 'string', 'description' => 'Unique client order ID'],
                        'circle' => ['type' => 'string', 'description' => $isMobile ? 'Circle name from operator/plan fetch. Required only when your account uses Mokshiq mobile recharge. Example: `Bihar and Jharkhand`.' : 'Not used for DTH recharge.'],
                        'geocode' => ['type' => 'string'],
                        'customer_number' => ['type' => 'string'],
                        'pincode' => ['type' => 'string'],
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
                'description' => 'Recharge accepted / completed',
                'content_type' => 'application/json',
                'is_default' => true,
                'example' => [
                    'status' => 'success',
                    'message' => 'Recharge completed successfully.',
                    'data' => [
                        'api_request_id' => 'RC_20260730123000_AB12',
                        'client_request_id' => 'UNIQUE_ID_123',
                        'account_number' => $accountNumber,
                        'amount' => 10,
                        'operator_ref' => 'OP998877',
                        'provider_txn_id' => 'RP123456789',
                        'created_at' => '2026-07-30T12:30:00+05:30',
                    ],
                ],
                'sort_order' => 1,
            ]
        );

        EndpointResponse::updateOrCreate(
            [
                'api_endpoint_id' => $endpoint->id,
                'status_code' => 400,
            ],
            [
                'description' => 'Recharge failed or rejected',
                'content_type' => 'application/json',
                'is_default' => false,
                'example' => [
                    'status' => 'failed',
                    'message' => 'Insufficient wallet balance. Please recharge your wallet.',
                ],
                'sort_order' => 2,
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
                    'request' => $requestExample,
                    'response' => [
                        'status' => 'success',
                        'message' => 'Recharge completed successfully.',
                        'data' => [
                            'api_request_id' => 'RC_20260730123000_AB12',
                            'client_request_id' => (string) $requestExample['client_request_id'],
                            'account_number' => (string) $requestExample['account_number'],
                            'amount' => 10,
                            'operator_ref' => 'OP998877',
                            'provider_txn_id' => 'RP123456789',
                            'created_at' => '2026-07-30T12:30:00+05:30',
                        ],
                    ],
                    'response_status' => 200,
                    'description' => "Successful {$label} response.",
                    'sort_order' => 1,
                ]
            );

            EndpointExample::updateOrCreate(
                [
                    'api_endpoint_id' => $endpoint->id,
                    'api_environment_id' => $env->id,
                    'title' => 'Failure Response',
                ],
                [
                    'request' => $requestExample,
                    'response' => [
                        'status' => 'failed',
                        'message' => 'Insufficient wallet balance. Please recharge your wallet.',
                    ],
                    'response_status' => 400,
                    'description' => "Failed {$label} due to wallet or provider error.",
                    'sort_order' => 2,
                ]
            );

            $baseUrl = rtrim((string) $env->base_url, '/');
            $circleLine = $isMobile
                ? "    \"circle\": \"Bihar and Jharkhand\",\n"
                : '';
            $curl = <<<BASH
curl -X POST "{$baseUrl}/api/v1/recharge" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{
    "account_number": "{$requestExample['account_number']}",
    "amount": 10,
    "operator_sp_key": {$requestExample['operator_sp_key']},
    "operator_type": "{$operatorType}",
{$circleLine}    "client_request_id": "{$requestExample['client_request_id']}"
  }'
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
