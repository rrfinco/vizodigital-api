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

class BillPaymentDocumentationSeeder extends Seeder
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
                'slug' => 'bill-payment',
            ],
            [
                'name' => 'Bill Payment',
                'description' => 'Fetch and pay credit card and utility bills via whitelisted provider APIs.',
                'icon' => 'receipt-percent',
                'status' => PublishStatus::Published,
                'show_in_sidebar' => true,
                'sort_order' => 3,
            ]
        );

        $group = ApiGroup::updateOrCreate(
            [
                'api_category_id' => $category->id,
                'slug' => 'credit-card',
            ],
            [
                'name' => 'Credit Card',
                'description' => 'Credit card bill fetch and payment APIs.',
                'status' => PublishStatus::Published,
                'sort_order' => 1,
            ]
        );

        $environments = ApiEnvironment::query()
            ->whereIn('slug', ['uat', 'production'])
            ->get();

        $this->seedFetchEndpoint($version, $group, $environments);
        $this->seedPayEndpoint($version, $group, $environments);

        if ($version->status === PublishStatus::Draft) {
            $version->forceFill(['status' => PublishStatus::Published])->save();
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ApiEnvironment>  $environments
     */
    private function seedFetchEndpoint(ApiVersion $version, ApiGroup $group, $environments): void
    {
        $endpoint = ApiEndpoint::updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => 'credit-card-bill-fetch',
            ],
            [
                'api_group_id' => $group->id,
                'name' => 'Credit Card Bill Fetch',
                'method' => HttpMethod::Post,
                'path' => '/api/v1/bill-payment/credit-card/bill-fetch',
                'summary' => 'Fetch credit card bill details',
                'description_md' => <<<'MD'
Fetch due details for a specific credit card before processing a payment.

Returns a `fetch_id` that is **required** for the Bill Pay API.

Your request is authenticated with a Bearer token. Provider credentials are applied server-side — never send Inspay username/token from the client.
MD,
                'status' => PublishStatus::Published,
                'rate_limit' => '60/min',
                'sort_order' => 1,
            ]
        );

        EndpointRequestBody::updateOrCreate(
            [
                'api_endpoint_id' => $endpoint->id,
                'content_type' => 'application/json',
            ],
            [
                'description' => 'Bill fetch request body',
                'required' => true,
                'example' => [
                    'mobile' => '9876543210',
                    'card' => '3008',
                    'opcode' => 'ICIC',
                    'orderid' => 'UNIQUE_ID_123',
                ],
                'schema' => [
                    'type' => 'object',
                    'required' => ['mobile', 'card', 'opcode', 'orderid'],
                    'properties' => [
                        'mobile' => ['type' => 'string', 'description' => '10-digit registered mobile number'],
                        'card' => ['type' => 'string', 'description' => 'Last 4 digits (or card identifier as required by operator)'],
                        'opcode' => ['type' => 'string', 'description' => 'Operator code, e.g. ICIC'],
                        'orderid' => ['type' => 'string', 'description' => 'Unique client order / request ID'],
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
                'description' => 'Bill details fetched successfully',
                'content_type' => 'application/json',
                'is_default' => true,
                'example' => [
                    'status' => 'success',
                    'message' => 'Transaction Successful',
                    'data' => [
                        'fetch_id' => 'TSB974ca11c49f641e08b17690a43631819',
                        'customer_name' => 'XXXX KUMAR AGARWAL',
                        'bill_date' => '08/12/2024',
                        'bill_due_date' => '28/12/2024',
                        'bill_amount' => 7475.12,
                        'minimum_due' => 2344,
                        'orderid' => 'UNIQUE_ID_123',
                    ],
                ],
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
                    'request' => [
                        'mobile' => '9876543210',
                        'card' => '3008',
                        'opcode' => 'ICIC',
                        'orderid' => 'UNIQUE_ID_123',
                    ],
                    'response' => [
                        'status' => 'success',
                        'message' => 'Transaction Successful',
                        'data' => [
                            'fetch_id' => 'TSB974ca11c49f641e08b17690a43631819',
                            'customer_name' => 'XXXX KUMAR AGARWAL',
                            'bill_date' => '08/12/2024',
                            'bill_due_date' => '28/12/2024',
                            'bill_amount' => 7475.12,
                            'minimum_due' => 2344,
                            'orderid' => 'UNIQUE_ID_123',
                        ],
                    ],
                    'response_status' => 200,
                    'description' => 'Successful bill fetch with fetch_id for payment.',
                    'sort_order' => 1,
                ]
            );

            $baseUrl = rtrim((string) $env->base_url, '/');
            $curl = <<<BASH
curl -X POST "{$baseUrl}/api/v1/bill-payment/credit-card/bill-fetch" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{
    "mobile": "9876543210",
    "card": "3008",
    "opcode": "ICIC",
    "orderid": "UNIQUE_ID_123"
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

    /**
     * @param  \Illuminate\Support\Collection<int, ApiEnvironment>  $environments
     */
    private function seedPayEndpoint(ApiVersion $version, ApiGroup $group, $environments): void
    {
        $endpoint = ApiEndpoint::updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => 'credit-card-bill-pay',
            ],
            [
                'api_group_id' => $group->id,
                'name' => 'Credit Card Bill Pay',
                'method' => HttpMethod::Post,
                'path' => '/api/v1/bill-payment/credit-card/bill-pay',
                'summary' => 'Pay credit card bill',
                'description_md' => <<<'MD'
Make a payment towards a credit card using the `fetch_id` from the Bill Fetch API.

**Notes**
- Amount is deducted from your **developer wallet** before the provider call.
- On provider failure, the wallet amount is refunded automatically.
- `pan` is optional but **mandatory for payments ≥ ₹50,000**.
MD,
                'status' => PublishStatus::Published,
                'rate_limit' => '60/min',
                'sort_order' => 2,
            ]
        );

        EndpointRequestBody::updateOrCreate(
            [
                'api_endpoint_id' => $endpoint->id,
                'content_type' => 'application/json',
            ],
            [
                'description' => 'Bill payment request body',
                'required' => true,
                'example' => [
                    'mobile' => '9876543210',
                    'card' => '3008',
                    'amount' => 7475.12,
                    'pan' => 'ABCDE1234F',
                    'fetch_id' => 'TSB974ca11c49f641e08b17690a43631819',
                    'opcode' => 'ICIC',
                    'orderid' => 'PAY_ID_123',
                ],
                'schema' => [
                    'type' => 'object',
                    'required' => ['mobile', 'card', 'amount', 'fetch_id', 'opcode', 'orderid'],
                    'properties' => [
                        'mobile' => ['type' => 'string'],
                        'card' => ['type' => 'string'],
                        'amount' => ['type' => 'number', 'description' => 'Payment amount in INR'],
                        'pan' => ['type' => 'string', 'description' => 'Mandatory when amount ≥ 50000'],
                        'fetch_id' => ['type' => 'string', 'description' => 'From bill-fetch response'],
                        'opcode' => ['type' => 'string'],
                        'orderid' => ['type' => 'string', 'description' => 'Unique payment order ID'],
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
                'description' => 'Payment successful',
                'content_type' => 'application/json',
                'is_default' => true,
                'example' => [
                    'status' => 'success',
                    'message' => 'Transaction Successful',
                    'data' => [
                        'txid' => 51749154,
                        'utr' => 'TJ014363062020A6D7C1',
                        'mobile' => '9876543210',
                        'card' => 'xxxx3008',
                        'amount' => 7475.12,
                        'orderid' => 'PAY_ID_123',
                        'wallet_balance' => 2524.88,
                    ],
                ],
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
                    'request' => [
                        'mobile' => '9876543210',
                        'card' => '3008',
                        'amount' => 7475.12,
                        'fetch_id' => 'TSB974ca11c49f641e08b17690a43631819',
                        'opcode' => 'ICIC',
                        'orderid' => 'PAY_ID_123',
                    ],
                    'response' => [
                        'status' => 'success',
                        'message' => 'Transaction Successful',
                        'data' => [
                            'txid' => 51749154,
                            'utr' => 'TJ014363062020A6D7C1',
                            'mobile' => '9876543210',
                            'card' => 'xxxx3008',
                            'amount' => 7475.12,
                            'orderid' => 'PAY_ID_123',
                            'wallet_balance' => 2524.88,
                        ],
                    ],
                    'response_status' => 200,
                    'description' => 'Successful credit card bill payment.',
                    'sort_order' => 1,
                ]
            );

            EndpointExample::updateOrCreate(
                [
                    'api_endpoint_id' => $endpoint->id,
                    'api_environment_id' => $env->id,
                    'title' => 'Failure Response (Duplicate)',
                ],
                [
                    'request' => [
                        'mobile' => '9876543210',
                        'card' => '3008',
                        'amount' => 7475.12,
                        'fetch_id' => 'TSB974ca11c49f641e08b17690a43631819',
                        'opcode' => 'ICIC',
                        'orderid' => 'PAY_ID_123',
                    ],
                    'response' => [
                        'status' => 'error',
                        'message' => 'Duplicate order_id. Use a unique orderid for each payment.',
                        'order_id' => 'PAY_ID_123',
                    ],
                    'response_status' => 422,
                    'description' => 'Duplicate order id rejection.',
                    'sort_order' => 2,
                ]
            );

            $baseUrl = rtrim((string) $env->base_url, '/');
            $curl = <<<BASH
curl -X POST "{$baseUrl}/api/v1/bill-payment/credit-card/bill-pay" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{
    "mobile": "9876543210",
    "card": "3008",
    "amount": 7475.12,
    "fetch_id": "TSB974ca11c49f641e08b17690a43631819",
    "opcode": "ICIC",
    "orderid": "PAY_ID_123"
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
