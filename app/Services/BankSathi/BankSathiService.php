<?php

namespace App\Services\BankSathi;

use App\Services\Portal\PortalSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankSathiService
{
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
     * @return array<string, mixed>
     */
    public function createLead(string $productId, ?int $categoryId = null, ?float $requiredAmount = null): array
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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $data = []): array
    {
        $baseUrl = rtrim($this->settings->banksathiBaseUrl(), '/');
        $apiKey = $this->settings->banksathiApiKey();
        $iv = $this->settings->banksathiIv();

        if ($baseUrl === '' || $apiKey === '' || $iv === '') {
            throw new \RuntimeException('Product API credentials are not configured. Ask an admin to set Base URL, IV, and X-API-Key in Settings.');
        }

        $url = $baseUrl.$path;
        $isGet = strtoupper($method) === 'GET';

        Log::info("Product API request: {$method} {$url}", [
            $isGet ? 'query' : 'body' => $isGet ? $data : array_merge($data, ['customer_id' => '***']),
        ]);

        $pending = Http::timeout(45)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'x-api-key' => $apiKey,
                'iv' => $iv,
            ]);

        $response = $isGet
            ? $pending->get($url, $data)
            : $pending->post($url, $data);

        Log::info("Product API response: {$url}", [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
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

    public function isSuccess(array $response): bool
    {
        $status = $response['status'] ?? false;

        if (is_bool($status)) {
            return $status;
        }

        return in_array(strtolower((string) $status), ['true', '1', 'success', 'ok'], true);
    }
}
