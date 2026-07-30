<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BillPayment\CreditCardBillPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CreditCardBillPaymentController extends Controller
{
    public function __construct(
        private readonly CreditCardBillPaymentService $billPayment
    ) {}

    public function fetch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'card' => ['required', 'string', 'min:4', 'max:19'],
            'opcode' => ['required', 'string', 'max:32'],
            'orderid' => ['required', 'string', 'max:100'],
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

        if (! $user->isOnboardingApproved()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your onboarding status is not approved yet.',
            ], 403);
        }

        try {
            $result = $this->billPayment->fetchBill($user, $validator->validated());
            $provider = $result['provider'];

            return response()->json([
                'status' => 'success',
                'message' => $provider['message'] ?? 'Bill fetched successfully.',
                'data' => [
                    'fetch_id' => $provider['fetch_id'] ?? null,
                    'customer_name' => $provider['customerName'] ?? null,
                    'bill_date' => $provider['billDate'] ?? null,
                    'bill_due_date' => $provider['billDueDate'] ?? null,
                    'bill_amount' => isset($provider['billAmount']) ? (float) $provider['billAmount'] : null,
                    'minimum_due' => isset($provider['minimum_due']) ? (float) $provider['minimum_due'] : null,
                    'orderid' => $validator->validated()['orderid'],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function pay(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'card' => ['required', 'string', 'min:4', 'max:19'],
            'amount' => ['required', 'numeric', 'min:1'],
            'fetch_id' => ['required', 'string', 'max:100'],
            'opcode' => ['required', 'string', 'max:32'],
            'orderid' => ['required', 'string', 'max:100'],
            'pan' => ['nullable', 'string', 'max:10'],
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

        if (! $user->isOnboardingApproved()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your onboarding status is not approved yet.',
            ], 403);
        }

        try {
            $validated = $validator->validated();
            $result = $this->billPayment->payBill($user, $validated);
            $provider = $result['provider'];
            $txn = $result['transaction'];

            return response()->json([
                'status' => 'success',
                'message' => $provider['message'] ?? 'Payment successful.',
                'data' => [
                    'txid' => $provider['txid'] ?? $txn->provider_txid,
                    'utr' => $provider['utr'] ?? $txn->utr,
                    'mobile' => $provider['mobile'] ?? $txn->mobile,
                    'card' => $provider['card'] ?? null,
                    'amount' => (float) ($provider['dr_amount'] ?? $txn->amount),
                    'orderid' => $provider['orderid'] ?? $txn->order_id,
                    'wallet_balance' => (float) $user->fresh()->wallet_balance,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
