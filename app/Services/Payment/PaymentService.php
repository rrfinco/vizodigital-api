<?php

namespace App\Services\Payment;

use App\Models\Deposit;
use App\Models\User;
use App\Services\Portal\PortalSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly PortalSettings $settings
    ) {}

    /**
     * Initiate payment request to RRFinco
     *
     * @return string Redirect URL to the gateway
     */
    public function initiatePayment(User $user, float $amount): string
    {
        if (! $this->settings->walletOnlineEnabled()) {
            throw new \Exception('Online payment is currently disabled. Please use bank transfer or contact support.');
        }

        $orderId = 'ORDER_'.date('YmdHis').'_'.Str::upper(Str::random(6));

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'amount' => $amount,
            'method' => Deposit::METHOD_ONLINE,
            'status' => 'pending',
        ]);

        $payload = [
            'account' => $this->settings->rrfincoAccount(),
            'merchant_id' => $this->settings->rrfincoMerchantId(),
            'amount' => $amount,
            'currency' => 'INR',
            'order_id' => $orderId,
            'cust_name' => $user->company_name ?: $user->name ?: 'Developer',
            'cust_email' => $user->email,
            'cust_phone' => $this->normalizeCustomerPhone($user->phone),
            'callback_url' => route('payment.webhook'),
            'redirect_url' => route('payment.redirect'),
        ];

        Log::info("Initiating RRFinco payment for Order: {$orderId}", ['payload' => $payload]);

        $apiUrl = 'https://pay.rrfinco.com/api/v1/payment/create';
        $token = $this->settings->rrfincoApiToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($apiUrl, $payload);

        Log::info("RRFinco payment response for Order: {$orderId}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if (! $response->successful()) {
            $deposit->update(['status' => 'failed']);
            throw new \Exception('Payment gateway returned error: '.($response->json('message') ?? $response->body()));
        }

        $paymentUrl = $response->json('payment_url') ?: $response->json('data.payment_url') ?: $response->json('url');

        if (! $paymentUrl) {
            $deposit->update(['status' => 'failed']);
            throw new \Exception('Payment gateway response did not contain a payment redirect URL.');
        }

        return $paymentUrl;
    }

    /**
     * Create a bank-transfer deposit request awaiting admin approval.
     */
    public function initiateBankTransfer(User $user, float $amount, string $utr, ?string $proofPath = null): Deposit
    {
        if (! $this->settings->walletBankTransferEnabled()) {
            throw new \Exception('Bank transfer is currently disabled.');
        }

        $utr = strtoupper(trim($utr));

        if ($utr === '') {
            throw new \Exception('UTR / transaction reference is required.');
        }

        $existing = Deposit::query()
            ->where('method', Deposit::METHOD_BANK_TRANSFER)
            ->where('utr', $utr)
            ->whereIn('status', ['pending', 'success'])
            ->exists();

        if ($existing) {
            throw new \Exception('This UTR / transaction reference has already been submitted.');
        }

        $orderId = 'BANK_'.date('YmdHis').'_'.Str::upper(Str::random(6));

        return Deposit::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'amount' => $amount,
            'method' => Deposit::METHOD_BANK_TRANSFER,
            'status' => 'pending',
            'utr' => $utr,
            'proof_path' => $proofPath,
        ]);
    }

    /**
     * Approve a pending bank-transfer deposit and credit the user wallet.
     */
    public function approveBankTransfer(Deposit $deposit, User $admin, ?string $notes = null): Deposit
    {
        return DB::transaction(function () use ($deposit, $admin, $notes) {
            /** @var Deposit $locked */
            $locked = Deposit::query()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isBankTransfer()) {
                throw new \Exception('Only bank transfer deposits can be approved this way.');
            }

            if (! $locked->isPending()) {
                throw new \Exception('This deposit has already been processed.');
            }

            $locked->update([
                'status' => 'success',
                'admin_notes' => $notes,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'gateway_ref' => $locked->utr,
            ]);

            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($locked->user_id);
            $user->creditWallet(
                (float) $locked->amount,
                'Deposit of ₹'.number_format((float) $locked->amount, 2).' via Bank Transfer (UTR: '.$locked->utr.')',
                $locked
            );

            Log::info("Approved bank transfer deposit {$locked->order_id} for user {$user->id}");

            return $locked->fresh();
        });
    }

    /**
     * Reject a pending bank-transfer deposit without crediting the wallet.
     */
    public function rejectBankTransfer(Deposit $deposit, User $admin, string $reason): Deposit
    {
        return DB::transaction(function () use ($deposit, $admin, $reason) {
            /** @var Deposit $locked */
            $locked = Deposit::query()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isBankTransfer()) {
                throw new \Exception('Only bank transfer deposits can be rejected this way.');
            }

            if (! $locked->isPending()) {
                throw new \Exception('This deposit has already been processed.');
            }

            $locked->update([
                'status' => 'rejected',
                'admin_notes' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            Log::info("Rejected bank transfer deposit {$locked->order_id}");

            return $locked->fresh();
        });
    }

    /**
     * RRFinco expects exactly 10 digits. Normalize +91 / spaces / dashes.
     */
    private function normalizeCustomerPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        } elseif (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        if (strlen($digits) === 10) {
            return $digits;
        }

        return '9876543210';
    }

    /**
     * Process Webhook callback from RRFinco
     */
    public function processWebhook(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;
        $status = strtolower($payload['status'] ?? '');
        $gatewayRef = $payload['txn_id'] ?? $payload['payment_id'] ?? $payload['transaction_id'] ?? null;

        if (! $orderId) {
            Log::warning('RRFinco webhook received without order_id', $payload);
            throw new \Exception('Missing order_id in payload.');
        }

        Log::info("Processing RRFinco Webhook for Order: {$orderId}, Status: {$status}", $payload);

        if (in_array($status, ['success', 'completed', 'paid', 'successful'], true)) {
            $statusUrl = 'https://pay.rrfinco.com/api/v1/payment/status/'.urlencode($orderId);
            $token = $this->settings->rrfincoApiToken();

            Log::info("Verifying RRFinco transaction status via status API for Order: {$orderId}");
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ])->get($statusUrl);

            Log::info("RRFinco status verification response for Order: {$orderId}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (! $response->successful() || ! in_array(strtolower($response->json('status') ?? ''), ['success', 'completed', 'paid', 'successful'], true)) {
                Log::warning("RRFinco status verification failed for Order: {$orderId}. Gateway status check failed.");
                throw new \Exception('Payment status verification failed.');
            }

            $status = strtolower($response->json('status') ?? 'success');
            $gatewayRef = $response->json('txn_id') ?? $gatewayRef;
        }

        DB::transaction(function () use ($orderId, $status, $gatewayRef, $payload) {
            /** @var Deposit $deposit */
            $deposit = Deposit::where('order_id', $orderId)->lockForUpdate()->first();

            if (! $deposit) {
                Log::error("Deposit record not found for Order ID: {$orderId}");
                throw new \Exception('Deposit record not found.');
            }

            if ($deposit->method === Deposit::METHOD_BANK_TRANSFER) {
                Log::warning("Ignoring gateway webhook for bank transfer deposit {$orderId}");

                return;
            }

            if ($deposit->status !== 'pending') {
                Log::info("Deposit Order ID: {$orderId} has already been processed with status: {$deposit->status}");

                return;
            }

            if (in_array($status, ['success', 'completed', 'paid', 'successful'], true)) {
                $deposit->update([
                    'status' => 'success',
                    'gateway_ref' => $gatewayRef,
                    'payload' => $payload,
                ]);

                /** @var User $user */
                $user = User::query()->lockForUpdate()->find($deposit->user_id);
                $user->creditWallet(
                    (float) $deposit->amount,
                    'Deposit of ₹'.number_format((float) $deposit->amount, 2)." via Payment Gateway (Order: {$orderId})",
                    $deposit
                );

                Log::info("Successfully credited wallet for User ID: {$user->id} with amount: {$deposit->amount}");
            } elseif (in_array($status, ['failed', 'failure', 'cancelled', 'rejected'], true)) {
                $deposit->update([
                    'status' => 'failed',
                    'gateway_ref' => $gatewayRef,
                    'payload' => $payload,
                ]);
                Log::info("Deposit Order ID: {$orderId} marked as failed.");
            } else {
                Log::warning("Unknown status '{$status}' received in webhook for Order: {$orderId}");
            }
        });
    }
}
