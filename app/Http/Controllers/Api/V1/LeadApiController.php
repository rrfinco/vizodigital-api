<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\WhitelabelUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProductApi\ProductApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

class LeadApiController extends Controller
{
    public function __construct(
        private readonly ProductApiService $productApi
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'string', 'max:64'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'required_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $validated = $validator->validated();

        return $this->respond($request, function (User $user) use ($validated) {
            $result = $this->productApi->createLead($user, $validated);
            $provider = $result['provider'];

            return [
                'message' => $provider['message'] ?? 'Lead created',
                'data' => $this->normalizeLead($provider['data'] ?? []),
                'fee' => $result['fee'],
                'wallet_balance' => $result['wallet_balance'],
            ];
        });
    }

    public function status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lead_code' => ['required', 'string', 'max:64'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $leadCode = (string) $validator->validated()['lead_code'];

        return $this->respond($request, function (User $user) use ($leadCode) {
            $result = $this->productApi->leadStatus($user, $leadCode);
            $provider = $result['provider'];

            return [
                'message' => $provider['message'] ?? 'Lead status fetched',
                'data' => $this->normalizeStatus($provider['data'] ?? [], $leadCode),
                'fee' => $result['fee'],
                'wallet_balance' => $result['wallet_balance'],
            ];
        });
    }

    /**
     * @param  callable(User): array{message: string, data: mixed, fee: float, wallet_balance: float}  $callback
     */
    private function respond(Request $request, callable $callback): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isOnboardingApproved()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your onboarding status is not approved yet.',
            ], 403);
        }

        try {
            $payload = $callback($user);

            return response()->json([
                'status' => 'success',
                'message' => $payload['message'],
                'data' => $payload['data'],
                'fee' => $payload['fee'],
                'wallet_balance' => $payload['wallet_balance'],
            ]);
        } catch (WhitelabelUnavailableException $e) {
            return $e->toJsonResponse();
        } catch (\RuntimeException $e) {
            $status = str_contains($e->getMessage(), 'not enabled') ? 403 : 400;

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $status);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    private function validationError(MessageBag $errors): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation error',
            'errors' => $errors,
        ], 422);
    }

    /**
     * @param  mixed  $data
     * @return array{lead_code: string, campaign_url: string}
     */
    private function normalizeLead(mixed $data): array
    {
        $row = is_array($data) ? $data : [];

        return [
            'lead_code' => (string) ($row['lead_code'] ?? ''),
            'campaign_url' => (string) ($row['campaign_url'] ?? $row['url'] ?? ''),
        ];
    }

    /**
     * @param  mixed  $data
     * @return array{lead_code: string, lead_status: string}
     */
    private function normalizeStatus(mixed $data, string $fallbackLeadCode): array
    {
        $row = is_array($data) ? $data : [];

        return [
            'lead_code' => (string) ($row['lead_code'] ?? $fallbackLeadCode),
            'lead_status' => (string) ($row['lead_status'] ?? 'pending'),
        ];
    }
}
