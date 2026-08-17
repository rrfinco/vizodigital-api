<?php

namespace App\Services\BankSathi;

use App\Services\Portal\PortalSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankSathiService
{
    public const SELF_EMPLOYED_OCCUPATION_ID = 2;

    public function __construct(
        private readonly PortalSettings $settings
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function allProductCategories(): array
    {
        return $this->get('/api/b2b/allProductCategory');
    }

    /**
     * @return array<string, mixed>
     */
    public function productsByCategory(int $categoryId): array
    {
        return $this->get('/api/b2b/productByCategory', [
            'category_id' => $categoryId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function productDetails(string $productId, ?int $categoryId = null, ?int $cardId = null): array
    {
        $customerId = $this->settings->banksathiCustomerId();

        if ($customerId === '') {
            throw new \RuntimeException('Product API credentials are not configured. Ask an admin to set Customer ID in Settings.');
        }

        $payload = array_filter([
            'customer_id' => $customerId,
            'product_id' => $productId,
            'category_id' => $categoryId,
            'categroy_id' => $categoryId,
            'card_id' => $cardId,
        ], fn ($value) => $value !== null && $value !== '');

        return $this->post('/api/b2b/otherProductDetails', $payload);
    }

    /**
     * Create or update a BankSathi customer profile and return their encrypted customer_id.
     *
     * @param  array{
     *     first_name: string,
     *     last_name: string,
     *     mobile_no: string,
     *     email: string,
     *     dob: string,
     *     company: int,
     *     occupation: int,
     *     monthly_salary: int|float,
     *     itr_amount: int|float,
     *     gender: string,
     *     pincode: int|string,
     *     address: string,
     *     category: string,
     *     category_id: int,
     *     pan: string,
     *     customer_id?: string|null
     * }  $data
     * @return array<string, mixed>
     */
    public function createLeadProfile(array $data): array
    {
        $occupation = (int) $data['occupation'];
        $isSelfEmployed = $occupation === self::SELF_EMPLOYED_OCCUPATION_ID;
        $pan = strtoupper(trim((string) $data['pan']));

        $payload = array_filter([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'mobile_no' => $data['mobile_no'],
            'email' => $data['email'],
            'dob' => $data['dob'],
            'company' => $data['company'],
            'occupation' => $occupation,
            'monthly_salary' => $isSelfEmployed ? 0 : $data['monthly_salary'],
            'itr_amount' => $isSelfEmployed ? $data['itr_amount'] : 0,
            'gender' => $data['gender'],
            'Gender' => $data['gender'],
            'pincode' => $data['pincode'],
            'address' => $data['address'],
            'Address' => $data['address'],
            'category' => $data['category'],
            'category_id' => $data['category_id'],
            'pan' => $pan,
            'pan_no' => $pan,
            'customer_id' => $data['customer_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return $this->postForm('/api/b2b/createLeadProfile', $payload, [
            'mobile_no' => $data['mobile_no'],
            'category_id' => $data['category_id'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function createLead(
        string $productId,
        ?int $categoryId = null,
        ?float $requiredAmount = null,
        ?string $customerId = null,
    ): array {
        $resolvedCustomerId = $customerId !== null && $customerId !== ''
            ? $customerId
            : $this->settings->banksathiCustomerId();

        if ($resolvedCustomerId === '') {
            throw new \RuntimeException('Product API credentials are not configured. Ask an admin to set Customer ID in Settings.');
        }

        $payload = array_filter([
            'customer_id' => $resolvedCustomerId,
            'product_id' => $productId,
            'category_id' => $categoryId,
            'categroy_id' => $categoryId,
            'required_amount' => $requiredAmount,
        ], fn ($value) => $value !== null && $value !== '');

        return $this->post('/api/b2b/lead', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function leadStatus(string $leadCode): array
    {
        return $this->get('/api/b2b/leadStatus', [
            'lead_code' => $leadCode,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        return $this->send('POST', $path, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function postForm(string $path, array $payload, array $query = []): array
    {
        return $this->send('POST', $path, $payload, asForm: true, query: $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $data = [], bool $asForm = false, array $query = []): array
    {
        $baseUrl = rtrim($this->settings->banksathiBaseUrl(), '/');
        $apiKey = $this->settings->banksathiApiKey();
        $iv = $this->settings->banksathiIv();

        if ($baseUrl === '' || $apiKey === '' || $iv === '') {
            throw new \RuntimeException('Product API credentials are not configured. Ask an admin to set Base URL, IV, and X-API-Key in Settings.');
        }

        $url = $baseUrl.$path;
        $isGet = strtoupper($method) === 'GET';

        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        Log::info("Product API request: {$method} {$url}", [
            $isGet ? 'query' : 'body' => $this->redactLogPayload($data),
        ]);

        $pending = Http::timeout(45)
            ->acceptJson()
            ->withHeaders([
                'x-api-key' => $apiKey,
                'iv' => $iv,
            ]);

        $pending = $asForm ? $pending->asForm() : $pending->asJson();

        $response = $isGet
            ? $pending->get($url, $data)
            : $pending->post($url, $data);

        Log::info("Product API response: {$url}", [
            'status' => $response->status(),
            'body' => $this->redactLogPayload($response->json() ?? ['raw' => $response->body()]),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Product API error: '.($response->json('message') ?? $response->body() ?: 'HTTP '.$response->status())
            );
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new \RuntimeException('Product API returned an invalid response.');
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>|mixed  $payload
     * @return array<string, mixed>|mixed
     */
    private function redactLogPayload(mixed $payload): mixed
    {
        if (! is_array($payload)) {
            return $payload;
        }

        $sensitive = [
            'customer_id',
            'pan',
            'pan_no',
            'mobile_no',
            'email',
            'first_name',
            'last_name',
            'address',
            'Address',
        ];

        $redacted = [];

        foreach ($payload as $key => $value) {
            if (in_array((string) $key, $sensitive, true)) {
                $redacted[$key] = '***';

                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redactLogPayload($value) : $value;
        }

        return $redacted;
    }

    public function isSuccess(array $response): bool
    {
        $status = $response['status'] ?? false;

        if (is_bool($status)) {
            return $status;
        }

        return in_array(strtolower((string) $status), ['true', '1', 'success', 'ok'], true);
    }
}
