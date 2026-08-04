<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Exceptions\WhitelabelUnavailableException;
use App\Models\User;
use App\Services\PlanApi\PlanApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

class PlanApiController extends Controller
{
    public function __construct(
        private readonly PlanApiService $planApi
    ) {}

    public function operatorFetch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'orderid' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->respond($request, function (User $user, array $data) {
            $result = $this->planApi->operatorFetch($user, $data);
            $provider = $result['provider'];

            return [
                'message' => $provider['message'] ?? 'Operator fetched successfully.',
                'data' => [
                    'number' => $provider['number'] ?? $data['mobile'],
                    'company' => $provider['company'] ?? null,
                    'circle' => $provider['circle'] ?? null,
                    'circle_code' => $provider['circle_code'] ?? null,
                    'orderid' => $data['orderid'],
                ],
                'fee' => $result['fee'],
                'wallet_balance' => $result['wallet_balance'],
            ];
        }, $validator->validated());
    }

    public function operatorPlanFetch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'opcode' => ['required', 'string', 'max:16'],
            'circle' => ['required', 'string', 'max:16'],
            'orderid' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->respond($request, function (User $user, array $data) {
            $result = $this->planApi->operatorPlanFetch($user, $data);
            $provider = $result['provider'];

            return [
                'message' => $provider['message'] ?? 'Operator plan fetched successfully.',
                'data' => [
                    'operator' => $provider['Operator'] ?? null,
                    'plans' => $provider['data'] ?? null,
                    'orderid' => $data['orderid'],
                ],
                'fee' => $result['fee'],
                'wallet_balance' => $result['wallet_balance'],
            ];
        }, $validator->validated());
    }

    public function dthPlanFetch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'dth_number' => ['required', 'string', 'max:32'],
            'opcode' => ['required', 'string', 'max:16'],
            'orderid' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->respond($request, function (User $user, array $data) {
            $result = $this->planApi->dthPlanFetch($user, $data);
            $provider = $result['provider'];

            return [
                'message' => $provider['message'] ?? 'DTH plan fetched successfully.',
                'data' => [
                    'operator' => $provider['Operator'] ?? null,
                    'plans' => $provider['data'] ?? null,
                    'orderid' => $data['orderid'],
                ],
                'fee' => $result['fee'],
                'wallet_balance' => $result['wallet_balance'],
            ];
        }, $validator->validated());
    }

    public function dthInfo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'dth_number' => ['required', 'string', 'max:32'],
            'opcode' => ['required', 'string', 'max:16'],
            'orderid' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->respond($request, function (User $user, array $data) {
            $result = $this->planApi->dthInfo($user, $data);
            $provider = $result['provider'];

            return [
                'message' => $provider['message'] ?? 'DTH customer info fetched successfully.',
                'data' => [
                    'customer' => $provider['data'] ?? null,
                    'orderid' => $data['orderid'],
                ],
                'fee' => $result['fee'],
                'wallet_balance' => $result['wallet_balance'],
            ];
        }, $validator->validated());
    }

    /**
     * @param  callable(User, array<string, mixed>): array{message: string, data: array<string, mixed>, fee: float, wallet_balance: float}  $callback
     * @param  array<string, mixed>  $validated
     */
    private function respond(Request $request, callable $callback, array $validated): JsonResponse
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
            $payload = $callback($user, $validated);

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
}
