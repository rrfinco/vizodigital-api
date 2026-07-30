<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Initiate a payment creation request via client API
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:500000',
        ]);

        try {
            $user = $request->user();
            $amount = (float) $request->input('amount');

            // Generate order and call RRFinco
            $paymentUrl = $this->paymentService->initiatePayment($user, $amount);

            // Find the newly created deposit order ID for response
            $deposit = \App\Models\Deposit::where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->first();

            return response()->json([
                'status' => 'success',
                'order_id' => $deposit?->order_id,
                'payment_url' => $paymentUrl,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
