<?php

namespace App\Services\Recharge;

use App\Services\Portal\PortalSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MokshiqService
{
    public function __construct(
        protected PortalSettings $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getOperators(): array
    {
        $base = rtrim($this->settings->mokshiqApiUrl(), '/');

        try {
            $response = Http::timeout(30)
                ->withHeaders($this->headers())
                ->get("{$base}/get_operator");

            if ($response->failed()) {
                Log::error('Mokshiq get_operator failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();

            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            Log::error('Mokshiq get_operator exception', ['exception' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param  array{operator: string, number: string, amount: float|string, circle: string}  $data
     * @return array{status: string, msg: string, bal?: float, errorCode?: string, rpid?: string, opid?: string}
     */
    public function createMobileRecharge(array $data): array
    {
        $base = rtrim($this->settings->mokshiqApiUrl(), '/');
        $pin = $this->settings->mokshiqPin();

        if ($pin === '' || $this->settings->mokshiqToken() === '' || $this->settings->mokshiqOrigin() === '') {
            return [
                'status' => 'failed',
                'msg' => 'Mokshiq credentials are not configured. Contact admin.',
                'errorCode' => 'CONFIG',
            ];
        }

        $form = [
            'operator' => (string) $data['operator'],
            'number' => (string) $data['number'],
            'amount' => (string) $data['amount'],
            'pin' => $pin,
            'circle' => (string) $data['circle'],
        ];

        try {
            Log::info('Mokshiq create_mobile_recharge request', [
                'operator' => $form['operator'],
                'number' => $form['number'],
                'amount' => $form['amount'],
                'circle' => $form['circle'],
            ]);

            $response = Http::timeout(30)
                ->withHeaders($this->headers())
                ->asMultipart()
                ->post("{$base}/create_mobile_recharge", $this->multipartFields($form));

            if ($response->failed()) {
                Log::error('Mokshiq create_mobile_recharge HTTP failure', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'status' => 'failed',
                    'msg' => $this->httpFailureMessage($response),
                    'errorCode' => 'HTTP_'.$response->status(),
                ];
            }

            $payload = $response->json();
            Log::info('Mokshiq create_mobile_recharge response', ['body' => $payload]);

            if (! is_array($payload) || $payload === []) {
                return [
                    'status' => 'failed',
                    'msg' => 'Empty Response from Provider',
                    'errorCode' => 'EMPTY_RESPONSE',
                ];
            }

            return $this->normalizeResult($payload);
        } catch (\Throwable $e) {
            Log::error('Mokshiq create_mobile_recharge exception', ['exception' => $e->getMessage()]);

            return [
                'status' => 'failed',
                'msg' => 'Internal Service Error: '.$e->getMessage(),
                'errorCode' => '500',
            ];
        }
    }

    /**
     * @param  array{operator: string, number: string, amount: float|string}  $data
     * @return array{status: string, msg: string, bal?: float, errorCode?: string, rpid?: string, opid?: string}
     */
    public function createDthRecharge(array $data): array
    {
        $base = rtrim($this->settings->mokshiqApiUrl(), '/');
        $pin = $this->settings->mokshiqPin();

        if ($pin === '' || $this->settings->mokshiqToken() === '' || $this->settings->mokshiqOrigin() === '') {
            return [
                'status' => 'failed',
                'msg' => 'Mokshiq credentials are not configured. Contact admin.',
                'errorCode' => 'CONFIG',
            ];
        }

        $form = [
            'operator' => (string) $data['operator'],
            'number' => (string) $data['number'],
            'amount' => (string) $data['amount'],
            'pin' => $pin,
        ];

        try {
            Log::info('Mokshiq create_dth_recharge request', [
                'operator' => $form['operator'],
                'number' => $form['number'],
                'amount' => $form['amount'],
            ]);

            $response = Http::timeout(30)
                ->withHeaders($this->headers())
                ->asMultipart()
                ->post("{$base}/create_dth_recharge", $this->multipartFields($form));

            if ($response->failed()) {
                Log::error('Mokshiq create_dth_recharge HTTP failure', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'status' => 'failed',
                    'msg' => $this->httpFailureMessage($response),
                    'errorCode' => 'HTTP_'.$response->status(),
                ];
            }

            $payload = $response->json();
            Log::info('Mokshiq create_dth_recharge response', ['body' => $payload]);

            if (! is_array($payload) || $payload === []) {
                return [
                    'status' => 'failed',
                    'msg' => 'Empty Response from Provider',
                    'errorCode' => 'EMPTY_RESPONSE',
                ];
            }

            return $this->normalizeResult($payload);
        } catch (\Throwable $e) {
            Log::error('Mokshiq create_dth_recharge exception', ['exception' => $e->getMessage()]);

            return [
                'status' => 'failed',
                'msg' => 'Internal Service Error: '.$e->getMessage(),
                'errorCode' => '500',
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->settings->mokshiqToken(),
            // Send exactly as registered in Mokshiq (Postman uses e.g. https://api.vizodigital.com/).
            'Origin' => trim($this->settings->mokshiqOrigin()),
            'Accept' => 'application/json',
        ];
    }

    /**
     * @param  array<string, string>  $fields
     * @return list<array{name: string, contents: string}>
     */
    private function multipartFields(array $fields): array
    {
        $parts = [];
        foreach ($fields as $name => $contents) {
            $parts[] = [
                'name' => $name,
                'contents' => $contents,
            ];
        }

        return $parts;
    }

    private function httpFailureMessage(\Illuminate\Http\Client\Response $response): string
    {
        $providerMsg = trim((string) (
            $response->json('message')
            ?? $response->json('msg')
            ?? $response->json('detail')
            ?? ''
        ));

        return $providerMsg !== ''
            ? $providerMsg
            : 'Provider Connection Failure (HTTP '.$response->status().')';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, msg: string, bal?: float, errorCode?: string, rpid?: string, opid?: string}
     */
    private function normalizeResult(array $payload): array
    {
        $normalized = [];
        foreach ($payload as $key => $value) {
            $normalized[strtolower((string) $key)] = $value;
        }

        $rawStatus = strtolower((string) ($normalized['status'] ?? $normalized['Status'] ?? ''));
        $msg = (string) ($normalized['message'] ?? $normalized['msg'] ?? $normalized['Message'] ?? '');
        $txnId = isset($normalized['txn_id'])
            ? trim((string) $normalized['txn_id'])
            : (isset($normalized['transaction_id'])
                ? trim((string) $normalized['transaction_id'])
                : (isset($normalized['reference_num']) ? trim((string) $normalized['reference_num']) : null));
        $opid = isset($normalized['opid'])
            ? trim((string) $normalized['opid'])
            : (isset($normalized['operator_ref']) ? trim((string) $normalized['operator_ref']) : null);

        if (in_array($rawStatus, ['success', 'successful', 'ok', '1', '2', 'true'], true)
            || str_contains(strtolower($msg), 'success')) {
            return [
                'status' => 'success',
                'msg' => $msg !== '' ? $msg : 'SUCCESS',
                'rpid' => $txnId,
                'opid' => $opid,
                'errorCode' => (string) ($normalized['error_code'] ?? $normalized['errorcode'] ?? '200'),
            ];
        }

        if (in_array($rawStatus, ['pending', 'processing', 'inprogress', 'in_progress'], true)) {
            return [
                'status' => 'pending',
                'msg' => $msg !== '' ? $msg : 'PENDING',
                'rpid' => $txnId,
                'errorCode' => (string) ($normalized['error_code'] ?? $normalized['errorcode'] ?? '200'),
            ];
        }

        return [
            'status' => 'failed',
            'msg' => $msg !== '' ? $msg : 'Failed',
            'errorCode' => (string) ($normalized['error_code'] ?? $normalized['errorcode'] ?? '500'),
        ];
    }
}
