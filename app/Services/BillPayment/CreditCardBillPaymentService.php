<?php

namespace App\Services\BillPayment;

use App\Models\BillPaymentTransaction;
use App\Models\User;
use App\Services\Inspay\InspayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditCardBillPaymentService
{
    public function __construct(
        private readonly InspayService $inspay
    ) {}

    /**
     * @param  array{mobile: string, card: string, opcode: string, orderid: string}  $data
     * @return array{transaction: BillPaymentTransaction, provider: array<string, mixed>}
     */
    public function fetchBill(User $user, array $data): array
    {
        $txn = BillPaymentTransaction::create([
            'user_id' => $user->id,
            'type' => 'credit_card_fetch',
            'order_id' => $data['orderid'],
            'mobile' => $data['mobile'],
            'card' => $data['card'],
            'opcode' => $data['opcode'],
            'status' => 'pending',
            'request_payload' => $data,
        ]);

        try {
            $provider = $this->inspay->creditCardBillFetch([
                'mobile' => $data['mobile'],
                'card' => $data['card'],
                'opcode' => $data['opcode'],
                'orderid' => $data['orderid'],
            ]);

            if (! $this->inspay->isSuccess($provider)) {
                $message = (string) ($provider['message'] ?? 'Bill fetch failed.');
                $txn->update([
                    'status' => 'failed',
                    'error_message' => $message,
                    'response_payload' => $provider,
                ]);

                throw new \RuntimeException($message);
            }

            $txn->update([
                'status' => 'success',
                'fetch_id' => $provider['fetch_id'] ?? null,
                'amount' => isset($provider['billAmount']) ? (float) $provider['billAmount'] : null,
                'response_payload' => $provider,
            ]);

            return [
                'transaction' => $txn->fresh(),
                'provider' => $provider,
            ];
        } catch (\Throwable $e) {
            if ($txn->status === 'pending') {
                $txn->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Debit developer wallet, then pay via Inspay. Refund on provider failure.
     *
     * @param  array{mobile: string, card: string, amount: float, fetch_id: string, opcode: string, orderid: string, pan?: string|null}  $data
     * @return array{transaction: BillPaymentTransaction, provider: array<string, mixed>}
     */
    public function payBill(User $user, array $data): array
    {
        $amount = round((float) $data['amount'], 2);

        if ($amount >= 50000 && empty($data['pan'])) {
            throw new \InvalidArgumentException('PAN is mandatory for payments of ₹50,000 or more.');
        }

        /** @var BillPaymentTransaction $txn */
        $txn = DB::transaction(function () use ($user, $data, $amount) {
            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ((float) $lockedUser->wallet_balance < $amount) {
                $available = (float) $lockedUser->wallet_balance;
                throw new \RuntimeException("Insufficient wallet balance. Required: ₹{$amount}, Available: ₹{$available}");
            }

            $existing = BillPaymentTransaction::query()
                ->where('user_id', $lockedUser->id)
                ->where('type', 'credit_card_pay')
                ->where('order_id', $data['orderid'])
                ->exists();

            if ($existing) {
                throw new \RuntimeException('This orderid has already been used for a credit card payment.');
            }

            $record = BillPaymentTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'credit_card_pay',
                'order_id' => $data['orderid'],
                'mobile' => $data['mobile'],
                'card' => $data['card'],
                'opcode' => $data['opcode'],
                'amount' => $amount,
                'fetch_id' => $data['fetch_id'],
                'pan' => $data['pan'] ?? null,
                'status' => 'pending',
                'request_payload' => array_merge($data, ['amount' => $amount]),
            ]);

            $lockedUser->debitWallet(
                $amount,
                "Credit card bill pay for {$data['card']} (Order: {$data['orderid']})",
                $record
            );

            return $record;
        });

        try {
            $provider = $this->inspay->creditCardBillPay([
                'mobile' => $data['mobile'],
                'card' => $data['card'],
                'amount' => $amount,
                'fetch_id' => $data['fetch_id'],
                'opcode' => $data['opcode'],
                'orderid' => $data['orderid'],
                'pan' => $data['pan'] ?? null,
            ]);

            if (! $this->inspay->isSuccess($provider)) {
                $message = (string) ($provider['message'] ?? 'Bill payment failed.');
                $this->refundAndFail($txn, $message, $provider);
                throw new \RuntimeException($message);
            }

            $txn->update([
                'status' => 'success',
                'provider_txid' => isset($provider['txid']) ? (string) $provider['txid'] : null,
                'utr' => $provider['utr'] ?? null,
                'response_payload' => $provider,
            ]);

            Log::info("Credit card bill pay success for order {$txn->order_id}");

            return [
                'transaction' => $txn->fresh(),
                'provider' => $provider,
            ];
        } catch (\Throwable $e) {
            if ($txn->fresh()?->status === 'pending') {
                $this->refundAndFail($txn, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>|null  $provider
     */
    private function refundAndFail(BillPaymentTransaction $txn, string $message, ?array $provider = null): void
    {
        DB::transaction(function () use ($txn, $message, $provider) {
            /** @var BillPaymentTransaction $locked */
            $locked = BillPaymentTransaction::query()->whereKey($txn->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                return;
            }

            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($locked->user_id);
            $user->creditWallet(
                (float) $locked->amount,
                "Refund for failed credit card bill pay (Order: {$locked->order_id})",
                $locked
            );

            $locked->update([
                'status' => 'failed',
                'error_message' => $message,
                'response_payload' => $provider,
            ]);
        });
    }
}
