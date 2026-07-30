<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Recharge\RechargeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RechargeController extends Controller
{
    protected RechargeService $rechargeService;

    public function __construct(RechargeService $rechargeService)
    {
        $this->rechargeService = $rechargeService;
    }

    /**
     * Process a recharge request from an API client
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_number' => ['required', 'string', 'min:4', 'max:20'],
            'amount' => ['required', 'numeric', 'min:1'],
            'operator_sp_key' => ['required', 'integer'],
            'operator_type' => ['required', 'string', 'in:mobile,dth,MOBILE,DTH'],
            'client_request_id' => ['nullable', 'string', 'max:100'],
            'geocode' => ['nullable', 'string', 'max:50'],
            'customer_number' => ['nullable', 'string', 'max:50'],
            'pincode' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Ensure user is onboarded and approved
        if (!$user->isOnboardingApproved()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your onboarding status is not approved yet.',
            ], 403);
        }

        try {
            $validated = $validator->validated();
            $transaction = $this->rechargeService->processRecharge($user, $validated);

            // Translate transaction model status into API response format
            $responseStatus = $transaction->status; // success, pending, failed
            $httpStatus = $responseStatus === 'failed' ? 400 : 200;

            return response()->json([
                'status' => $responseStatus,
                'message' => $responseStatus === 'success'
                    ? 'Recharge completed successfully.'
                    : ($responseStatus === 'pending' ? 'Recharge is pending with operator.' : ($transaction->error_message ?? 'Recharge failed.')),
                'data' => [
                    'api_request_id' => $transaction->api_request_id,
                    'client_request_id' => $transaction->client_request_id,
                    'account_number' => $transaction->account_number,
                    'amount' => (float) $transaction->amount,
                    'operator_ref' => $transaction->opid,
                    'provider_txn_id' => $transaction->rpid,
                    'created_at' => $transaction->created_at->toIso8601String(),
                ]
            ], $httpStatus);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
