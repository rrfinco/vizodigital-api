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
use App\Services\Taxation\TaxationCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class TaxationDocumentationSeeder extends Seeder
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
                'slug' => 'taxation',
            ],
            [
                'name' => 'Taxation',
                'description' => 'Create clients and tax/compliance orders. Prices come from the admin catalog — send service_id, never amount. No commission; wallet debit on order confirm.',
                'icon' => 'briefcase',
                'status' => PublishStatus::Published,
                'show_in_sidebar' => true,
                'sort_order' => 6,
            ]
        );

        $group = ApiGroup::updateOrCreate(
            [
                'api_category_id' => $category->id,
                'slug' => 'taxation-services',
            ],
            [
                'name' => 'Clients & orders',
                'description' => 'Create a client, list catalog services, then place an order with service_id.',
                'status' => PublishStatus::Published,
                'sort_order' => 1,
            ]
        );

        $environments = ApiEnvironment::query()
            ->whereIn('slug', ['uat', 'production'])
            ->get();

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'list-taxation-services',
            'name' => 'List taxation services',
            'method' => HttpMethod::Get,
            'path' => '/api/v1/taxation/services',
            'summary' => 'Catalog of service_id values and admin prices',
            'description' => <<<'MD'
Returns active services for your account. Pass the integer `service_id` into **Create taxation order**. Do not send the service name or price.

Optional query: `category` (category slug).

**Access:** Admin (B2C) or partner (white-label) must enable **Taxation** (`taxation`) on Plan API access.
MD,
            'sort_order' => 1,
            'is_post' => false,
            'example_request' => null,
            'example_response' => [
                'status' => 'success',
                'message' => 'Services fetched.',
                'data' => [
                    [
                        'service_id' => 1,
                        'category' => 'Company And LLP And NGO Incorporation',
                        'category_slug' => 'company-and-llp-and-ngo-incorporation',
                        'name' => 'PRIVATE LIMITED COMPANY REGISTRATION',
                        'price' => 5499,
                    ],
                ],
                'fee' => 0,
                'wallet_balance' => 10000.00,
            ],
            'schema' => null,
        ]);

        $clientBody = [
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
        ];

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'create-taxation-client',
            'name' => 'Create taxation client',
            'method' => HttpMethod::Post,
            'path' => '/api/v1/taxation/clients',
            'summary' => 'Create a client. All personal and address fields are required.',
            'description' => <<<'MD'
Creates a client record on the portal. Returns `client_id` — pass that into **Create taxation order**.

All fields from the Create Client form are **required**, including `middle_name`.

`client_request_id` is optional and must be unique per developer.
MD,
            'sort_order' => 2,
            'is_post' => true,
            'example_request' => $clientBody,
            'example_response' => [
                'status' => 'success',
                'message' => 'Client created successfully.',
                'data' => array_merge(['client_id' => 42], $clientBody, ['created_at' => '2026-08-19T08:00:00+00:00']),
                'fee' => 0,
                'wallet_balance' => 10000.00,
            ],
            'schema' => [
                'type' => 'object',
                'required' => [
                    'first_name', 'middle_name', 'last_name', 'email', 'phone', 'pan', 'aadhaar',
                    'residence_address', 'residence_city', 'residence_pincode', 'residence_state',
                    'office_address', 'office_city', 'office_pincode', 'office_state',
                ],
                'properties' => [
                    'first_name' => ['type' => 'string'],
                    'middle_name' => ['type' => 'string'],
                    'last_name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'phone' => ['type' => 'string', 'description' => '10-digit mobile'],
                    'pan' => ['type' => 'string', 'description' => 'ABCDE1234F'],
                    'aadhaar' => ['type' => 'string', 'description' => '12 digits'],
                    'residence_address' => ['type' => 'string'],
                    'residence_city' => ['type' => 'string'],
                    'residence_pincode' => ['type' => 'string'],
                    'residence_state' => ['type' => 'string'],
                    'office_address' => ['type' => 'string'],
                    'office_city' => ['type' => 'string'],
                    'office_pincode' => ['type' => 'string'],
                    'office_state' => ['type' => 'string'],
                    'client_request_id' => ['type' => 'string'],
                ],
            ],
        ]);

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'list-taxation-clients',
            'name' => 'List taxation clients',
            'method' => HttpMethod::Get,
            'path' => '/api/v1/taxation/clients',
            'summary' => 'List clients created by the authenticated developer',
            'description' => 'Returns up to 100 of your most recent clients.',
            'sort_order' => 3,
            'is_post' => false,
            'example_request' => null,
            'example_response' => [
                'status' => 'success',
                'message' => 'Clients fetched.',
                'data' => [
                    array_merge(['client_id' => 42], $clientBody),
                ],
                'fee' => 0,
                'wallet_balance' => 10000.00,
            ],
            'schema' => null,
        ]);

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'create-taxation-order',
            'name' => 'Create taxation order',
            'method' => HttpMethod::Post,
            'path' => '/api/v1/taxation/orders',
            'summary' => 'Place an order using client_id and service_id. Wallet is debited at catalog price.',
            'description' => <<<'MD'
Debits the **catalog price** (set by admin) from the developer wallet. White-label developers also debit partner float for the same amount. There is **no commission**.

Do not send `amount` or service name — only `client_id` + `service_id`.

On success the order is `pending` (payment confirmed). Next: **Upload taxation documents**. Admin then marks documents verified and approved.

**Access:** Admin enables Taxation per B2C developer. White-label: admin enables the partner, then the partner enables each developer.
MD,
            'sort_order' => 4,
            'is_post' => true,
            'example_request' => [
                'client_id' => 42,
                'service_id' => 1,
                'client_request_id' => 'ORDER_001',
            ],
            'example_response' => [
                'status' => 'success',
                'message' => 'Order created successfully.',
                'data' => [
                    'order_id' => 10,
                    'api_request_id' => 'TX260819080011abcd1234',
                    'client_request_id' => 'ORDER_001',
                    'client_id' => 42,
                    'service_id' => 1,
                    'service_name' => 'PRIVATE LIMITED COMPANY REGISTRATION',
                    'amount' => 5499,
                    'status' => 'pending',
                    'documents_status' => 'pending',
                    'documents' => [],
                    'created_at' => '2026-08-19T08:00:11+00:00',
                ],
                'fee' => 5499,
                'wallet_balance' => 4501.00,
            ],
            'schema' => [
                'type' => 'object',
                'required' => ['client_id', 'service_id'],
                'properties' => [
                    'client_id' => ['type' => 'integer', 'description' => 'From Create taxation client'],
                    'service_id' => ['type' => 'integer', 'description' => 'From List taxation services'],
                    'client_request_id' => ['type' => 'string'],
                ],
            ],
        ]);

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'list-taxation-orders',
            'name' => 'List taxation orders',
            'method' => HttpMethod::Get,
            'path' => '/api/v1/taxation/orders',
            'summary' => 'List your taxation orders',
            'description' => 'Returns up to 100 of your most recent orders.',
            'sort_order' => 5,
            'is_post' => false,
            'example_request' => null,
            'example_response' => [
                'status' => 'success',
                'message' => 'Orders fetched.',
                'data' => [
                    [
                        'order_id' => 10,
                        'api_request_id' => 'TX260819080011abcd1234',
                        'client_id' => 42,
                        'service_id' => 1,
                        'amount' => 5499,
                        'status' => 'pending',
                        'documents_status' => 'pending',
                    ],
                ],
                'fee' => 0,
                'wallet_balance' => 4501.00,
            ],
            'schema' => null,
        ]);

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'get-taxation-order',
            'name' => 'Get taxation order',
            'method' => HttpMethod::Get,
            'path' => '/api/v1/taxation/orders/{order}',
            'summary' => 'Fetch one order including document verification status',
            'description' => 'Returns the order, `documents_status`, and uploaded files. Use after payment to check whether admin has verified or approved the documents.',
            'sort_order' => 6,
            'is_post' => false,
            'example_request' => null,
            'example_response' => [
                'status' => 'success',
                'message' => 'Order fetched.',
                'data' => [
                    'order_id' => 10,
                    'status' => 'processing',
                    'documents_status' => 'submitted',
                    'documents_note' => null,
                    'documents' => [
                        [
                            'document_id' => 1,
                            'type' => 'pan_card',
                            'type_label' => 'PAN card',
                            'original_name' => 'pan.pdf',
                            'status' => 'pending',
                        ],
                    ],
                ],
                'fee' => 0,
                'wallet_balance' => 4501.00,
            ],
            'schema' => null,
        ]);

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'upload-taxation-documents',
            'name' => 'Upload taxation documents',
            'method' => HttpMethod::Post,
            'path' => '/api/v1/taxation/orders/{order}/documents',
            'summary' => 'Upload documents after payment confirmation',
            'description' => <<<'MD'
Call this **after** create-order (wallet debit / payment confirmation).

`multipart/form-data` with 1–10 files. Allowed types: `pan_card`, `aadhaar_card`, `photograph`, `address_proof`, `bank_statement`, `gst_certificate`, `incorporation_certificate`, `other`.

Files: PDF / JPG / PNG / WEBP, max 10 MB each.

On upload the order moves to `processing` and `documents_status` becomes `submitted`. Admin can then **verify** and **approve**. If admin rejects, re-upload replacements.
MD,
            'sort_order' => 7,
            'is_post' => true,
            'is_multipart' => true,
            'example_request' => [
                'documents' => [
                    ['type' => 'pan_card', 'file' => 'pan.pdf'],
                    ['type' => 'aadhaar_card', 'file' => 'aadhaar.pdf'],
                ],
            ],
            'example_response' => [
                'status' => 'success',
                'message' => 'Documents uploaded. Admin will verify and approve them.',
                'data' => [
                    'order_id' => 10,
                    'status' => 'processing',
                    'documents_status' => 'submitted',
                    'documents' => [
                        [
                            'document_id' => 1,
                            'type' => 'pan_card',
                            'status' => 'pending',
                        ],
                    ],
                ],
                'fee' => 0,
                'wallet_balance' => 4501.00,
            ],
            'schema' => [
                'type' => 'object',
                'required' => ['documents'],
                'properties' => [
                    'documents' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['type', 'file'],
                            'properties' => [
                                'type' => ['type' => 'string'],
                                'file' => ['type' => 'string', 'format' => 'binary'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->seedEndpoint($version, $group, $environments, [
            'slug' => 'list-taxation-documents',
            'name' => 'List taxation documents',
            'method' => HttpMethod::Get,
            'path' => '/api/v1/taxation/orders/{order}/documents',
            'summary' => 'List uploaded documents and admin verification status',
            'description' => '`documents_status` values: `pending`, `submitted`, `verified`, `approved`, `rejected`.',
            'sort_order' => 8,
            'is_post' => false,
            'example_request' => null,
            'example_response' => [
                'status' => 'success',
                'message' => 'Documents fetched.',
                'data' => [
                    'order_id' => 10,
                    'documents_status' => 'verified',
                    'documents_note' => null,
                    'documents' => [
                        [
                            'document_id' => 1,
                            'type' => 'pan_card',
                            'type_label' => 'PAN card',
                            'original_name' => 'pan.pdf',
                            'status' => 'verified',
                        ],
                    ],
                ],
                'fee' => 0,
                'wallet_balance' => 4501.00,
            ],
            'schema' => null,
        ]);
    }

    /**
     * @param  Collection<int, ApiEnvironment>  $environments
     * @param  array<string, mixed>  $config
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
                'method' => $config['method'],
                'path' => $config['path'],
                'summary' => $config['summary'],
                'description_md' => $config['description'],
                'status' => PublishStatus::Published,
                'access_service_key' => TaxationCatalog::SERVICE_ACCESS_KEY,
                'rate_limit' => '60/min',
                'sort_order' => $config['sort_order'],
            ]
        );

        if ($config['schema'] !== null) {
            EndpointRequestBody::updateOrCreate(
                [
                    'api_endpoint_id' => $endpoint->id,
                    'content_type' => ($config['is_multipart'] ?? false) ? 'multipart/form-data' : 'application/json',
                ],
                [
                    'description' => $config['name'].' request body',
                    'required' => true,
                    'example' => $config['example_request'],
                    'schema' => $config['schema'],
                    'sort_order' => 1,
                ]
            );
        }

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

        $jsonBody = $config['example_request']
            ? json_encode($config['example_request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : null;

        foreach ($environments as $env) {
            EndpointExample::updateOrCreate(
                [
                    'api_endpoint_id' => $endpoint->id,
                    'api_environment_id' => $env->id,
                    'title' => 'Success Response',
                ],
                [
                    'request' => $config['example_request'] ?? [],
                    'response' => $config['example_response'],
                    'response_status' => 200,
                    'description' => 'Successful '.$config['name'].' call.',
                    'sort_order' => 1,
                ]
            );

            $baseUrl = rtrim((string) $env->base_url, '/');

            if ($config['is_multipart'] ?? false) {
                $curl = <<<BASH
curl -X POST "{$baseUrl}{$endpoint->path}" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Accept: application/json" \\
  -F "documents[0][type]=pan_card" \\
  -F "documents[0][file]=@pan.pdf" \\
  -F "documents[1][type]=aadhaar_card" \\
  -F "documents[1][file]=@aadhaar.pdf"
BASH;
            } elseif ($config['is_post']) {
                $curl = <<<BASH
curl -X POST "{$baseUrl}{$endpoint->path}" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Content-Type: application/json" \\
  -H "Accept: application/json" \\
  -d '{$jsonBody}'
BASH;
            } else {
                $curl = <<<BASH
curl -X GET "{$baseUrl}{$endpoint->path}" \\
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
