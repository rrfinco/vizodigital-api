<?php

namespace App\Services\Inspay;

use App\Services\Portal\PortalSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InspayService
{
    private const BASE_URL = 'https://inspay.in/v4';

    public function __construct(
        private readonly PortalSettings $settings
    ) {}

    /**
     * @param  array{mobile: string, card: string, opcode: string, orderid: string}  $data
     * @return array<string, mixed>
     */
    public function creditCardBillFetch(array $data): array
    {
        return $this->post('credit_card/bill_fetch', [
            'mobile' => $data['mobile'],
            'card' => $data['card'],
            'opcode' => $data['opcode'],
            'orderid' => $data['orderid'],
        ]);
    }

    /**
     * @param  array{mobile: string, card: string, amount: float|int|string, fetch_id: string, opcode: string, orderid: string, pan?: string|null}  $data
     * @return array<string, mixed>
     */
    public function creditCardBillPay(array $data): array
    {
        $payload = [
            'mobile' => $data['mobile'],
            'card' => $data['card'],
            'amount' => $data['amount'],
            'fetch_id' => $data['fetch_id'],
            'opcode' => $data['opcode'],
            'orderid' => $data['orderid'],
        ];

        if (! empty($data['pan'])) {
            $payload['pan'] = $data['pan'];
        }

        return $this->post('credit_card/bill_pay', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        $username = $this->settings->inspayUsername();
        $token = $this->settings->inspayToken();

        if ($username === '' || $token === '') {
            throw new \RuntimeException('Inspay credentials are not configured. Ask an admin to set username and token in Settings.');
        }

        $body = array_merge([
            'username' => $username,
            'token' => $token,
        ], $payload);

        $url = self::BASE_URL.'/'.$path;

        Log::info("Inspay request: {$url}", [
            'payload' => array_merge($body, ['token' => '***']),
        ]);

        $response = Http::timeout(45)
            ->acceptJson()
            ->asJson()
            ->post($url, $body);

        Log::info("Inspay response: {$url}", [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Inspay API error: '.($response->json('message') ?? $response->body() ?: 'HTTP '.$response->status())
            );
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new \RuntimeException('Inspay returned an invalid response.');
        }

        return $json;
    }

    public function isSuccess(array $response): bool
    {
        $status = strtolower((string) ($response['status'] ?? ''));

        return in_array($status, ['success', 'successful', 'ok'], true);
    }
}
