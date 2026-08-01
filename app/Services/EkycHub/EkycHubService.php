<?php

namespace App\Services\EkycHub;

use App\Services\Portal\PortalSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EkycHubService
{
    private const BASE_URL = 'https://connect.ekychub.in/v3/verification';

    public function __construct(
        private readonly PortalSettings $settings
    ) {}

    /**
     * @param  array{mobile: string, orderid: string}  $data
     * @return array<string, mixed>
     */
    public function operatorFetch(array $data): array
    {
        return $this->get('operator_fetch', [
            'mobile' => $data['mobile'],
            'orderid' => $data['orderid'],
        ]);
    }

    /**
     * @param  array{mobile: string, opcode: string, circle: string, orderid: string}  $data
     * @return array<string, mixed>
     */
    public function operatorPlanFetch(array $data): array
    {
        return $this->get('operator_plan_fetch', [
            'mobile' => $data['mobile'],
            'opcode' => $data['opcode'],
            'circle' => $data['circle'],
            'orderid' => $data['orderid'],
        ]);
    }

    /**
     * @param  array{dth_number: string, opcode: string, orderid: string}  $data
     * @return array<string, mixed>
     */
    public function dthPlanFetch(array $data): array
    {
        return $this->get('dth_plan_fetch', [
            'dth_number' => $data['dth_number'],
            'opcode' => $data['opcode'],
            'orderid' => $data['orderid'],
        ]);
    }

    /**
     * @param  array{dth_number: string, opcode: string, orderid: string}  $data
     * @return array<string, mixed>
     */
    public function dthInfo(array $data): array
    {
        return $this->get('dth_info', [
            'dth_number' => $data['dth_number'],
            'opcode' => $data['opcode'],
            'orderid' => $data['orderid'],
        ]);
    }

    /**
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query): array
    {
        $username = $this->settings->ekycHubUsername();
        $token = $this->settings->ekycHubToken();

        if ($username === '' || $token === '') {
            throw new \RuntimeException('EkycHub credentials are not configured. Ask an admin to set username and token in Settings.');
        }

        $params = array_merge([
            'username' => $username,
            'token' => $token,
        ], $query);

        $url = self::BASE_URL.'/'.$path;

        Log::info("EkycHub request: {$url}", [
            'params' => array_merge($params, ['token' => '***']),
        ]);

        $response = Http::timeout(45)
            ->acceptJson()
            ->get($url, $params);

        Log::info("EkycHub response: {$url}", [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'EkycHub API error: '.($response->json('message') ?? $response->body() ?: 'HTTP '.$response->status())
            );
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new \RuntimeException('EkycHub returned an invalid response.');
        }

        return $json;
    }

    public function isSuccess(array $response): bool
    {
        $status = strtolower((string) ($response['status'] ?? ''));

        return in_array($status, ['success', 'successful', 'ok'], true);
    }
}
