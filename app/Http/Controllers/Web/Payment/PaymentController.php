<?php

namespace App\Http\Controllers\Web\Payment;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Initiate a payment deposit attempt and redirect user to the gateway
     */
    public function initiate(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:500000',
        ]);

        try {
            $user = $request->user();
            $amount = (float) $request->input('amount');

            $redirectUrl = $this->paymentService->initiatePayment($user, $amount);

            return redirect()->away($redirectUrl);
        } catch (\Throwable $e) {
            Log::error("Failed initiating payment: " . $e->getMessage());
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    /**
     * Handle payment verification callback redirect (GET)
     */
    public function callback(Request $request): View
    {
        $orderId = $request->query('order_id');
        $deposit = null;

        if ($orderId) {
            $deposit = Deposit::where('order_id', $orderId)->first();
        }

        return view('payment.redirect', [
            'deposit' => $deposit,
            'orderId' => $orderId
        ]);
    }

    /**
     * Handle payment status callback webhook (POST) from RRFinco Gateway
     */
    public function webhook(Request $request): JsonResponse
    {
        Log::info("Incoming RRFinco Webhook Request", [
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);

        try {
            $this->paymentService->processWebhook($request->all());
            return response()->json(['status' => 'processed']);
        } catch (\Throwable $e) {
            Log::error("Error handling RRFinco webhook: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
