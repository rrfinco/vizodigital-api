<?php

namespace App\Services\BillPayment;

use App\Exceptions\WhitelabelUnavailableException;
use App\Models\BillPaymentTransaction;
use App\Models\User;
use App\Models\UserBillOperatorCommission;
use App\Models\UserPlanApiAccess;
use App\Models\WhitelabelBillOperatorCommission;
use App\Models\WhitelabelPlanApiAccess;
use App\Services\Inspay\InspayService;
use App\Services\Whitelabel\WhitelabelBillingGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditCardBillPaymentService
{
    public const SERVICE_CREDIT_CARD_FETCH = 'credit_card_fetch';

    public function __construct(
        private readonly InspayService $inspay,
        private readonly WhitelabelBillingGate $whitelabelBillingGate,
    ) {}

    /**
     * Debit per-call fee from developer wallet (same path as Plan API), then fetch bill via Inspay.
     * Fee is refunded if the provider call fails.
     *
     * @param  array{mobile: string, card: string, opcode: string, orderid: string}  $data
     * @return array{transaction: BillPaymentTransaction, provider: array<string, mixed>, fee: float, wallet_balance: float}
     *
     * @throws WhitelabelUnavailableException
     */
    public function fetchBill(User $user, array $data): array
    {
        $service = self::SERVICE_CREDIT_CARD_FETCH;
        $orderid = $data['orderid'];

        $access = UserPlanApiAccess::query()
            ->where('user_id', $user->id)
            ->where('service', $service)
            ->first();

        if (! $access || ! $access->isActive()) {
            throw new \RuntimeException('This API is not enabled for your account. Contact admin.');
        }

        $userFee = round((float) $access->per_call_fee, 2);
        $wlMargin = 0.0;
        $isWhitelabelUser = (bool) $user->whitelabel_id;

        if ($isWhitelabelUser) {
            $wlAccess = WhitelabelPlanApiAccess::resolveFor((int) $user->whitelabel_id, $service);

            if (! $wlAccess || ! $wlAccess['status']) {
                throw new WhitelabelUnavailableException(
                    WhitelabelUnavailableException::REASON_SUSPENDED,
                    (int) $user->whitelabel_id
                );
            }

            $wlFee = round($wlAccess['per_call_fee'], 2);
            // Debit mirrors developer fee on float; margin credit = partner markup.
            $wlMargin = max(0, round($userFee - $wlFee, 2));
        }

        $debited = false;
        $refunded = false;

        DB::transaction(function () use ($user, $userFee, $service, $orderid, &$debited) {
            // Float must cover the developer-facing fee (wholesale mirror).
            $wl = $this->whitelabelBillingGate->lockForDebit($user, $userFee);

            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($userFee > 0) {
                if ((float) $lockedUser->wallet_balance < $userFee) {
                    $available = (float) $lockedUser->wallet_balance;
                    throw new \RuntimeException("Insufficient wallet balance. Please recharge your wallet. Required: ₹{$userFee}, Available: ₹{$available}");
                }

                $lockedUser->debitWallet(
                    $userFee,
                    "Plan API fee ({$service}) order {$orderid}",
                );
                $this->whitelabelBillingGate->debit(
                    $wl,
                    $userFee,
                    "Plan API fee ({$service}) order {$orderid}",
                );
                $debited = true;
            }
        });

        $txn = BillPaymentTransaction::create([
            'user_id' => $user->id,
            'type' => 'credit_card_fetch',
            'order_id' => $orderid,
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
                'orderid' => $orderid,
            ]);

            if (! $this->inspay->isSuccess($provider)) {
                $message = (string) ($provider['message'] ?? 'Bill fetch failed.');
                $txn->update([
                    'status' => 'failed',
                    'error_message' => $message,
                    'response_payload' => $provider,
                ]);

                if ($debited) {
                    $this->refundFetchFee($user, $userFee, $service, $orderid);
                    $refunded = true;
                }

                throw new \RuntimeException($message);
            }

            $txn->update([
                'status' => 'success',
                'fetch_id' => $provider['fetch_id'] ?? null,
                'amount' => isset($provider['billAmount']) ? (float) $provider['billAmount'] : null,
                'response_payload' => $provider,
            ]);

            if ($isWhitelabelUser && $wlMargin > 0) {
                DB::transaction(function () use ($user, $wlMargin, $service, $orderid) {
                    $wl = $this->whitelabelBillingGate->lockForUpdate($user);
                    $this->whitelabelBillingGate->creditCommission(
                        $wl,
                        $wlMargin,
                        "Plan API margin ({$service}) order {$orderid}",
                    );
                });
            }

            return [
                'transaction' => $txn->fresh(),
                'provider' => $provider,
                'fee' => $userFee,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        } catch (\Throwable $e) {
            if ($txn->status === 'pending') {
                $txn->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            if ($debited && ! $refunded) {
                $this->refundFetchFee($user, $userFee, $service, $orderid);
            }

            throw $e;
        }
    }

    /**
     * Debit developer + WL float, then pay via Inspay. Credit commissions on success; refund both on failure.
     *
     * @param  array{mobile: string, card: string, amount: float, fetch_id: string, opcode: string, orderid: string, pan?: string|null}  $data
     * @return array{transaction: BillPaymentTransaction, provider: array<string, mixed>}
     *
     * @throws WhitelabelUnavailableException
     */
    public function payBill(User $user, array $data): array
    {
        $amount = round((float) $data['amount'], 2);
        $opcode = (string) $data['opcode'];

        if ($amount >= 50000 && empty($data['pan'])) {
            throw new \InvalidArgumentException('PAN is mandatory for payments of ₹50,000 or more.');
        }

        $commission = UserBillOperatorCommission::resolveFor($user->id, $opcode);

        if (! $commission['status']) {
            throw new \RuntimeException('This operator is currently inactive or disabled for your account.');
        }

        $commissionAmount = UserBillOperatorCommission::calculateAmount(
            $commission['commission_type'],
            $commission['commission_value'],
            $amount
        );

        $wlCommissionAmount = 0.0;
        if ($user->whitelabel_id) {
            $wlCommission = WhitelabelBillOperatorCommission::resolveFor((int) $user->whitelabel_id, $opcode);

            if (! $wlCommission['status']) {
                throw new WhitelabelUnavailableException(
                    WhitelabelUnavailableException::REASON_SUSPENDED,
                    (int) $user->whitelabel_id
                );
            }

            $wlCommissionAmount = WhitelabelBillOperatorCommission::calculateAmount(
                $wlCommission['commission_type'],
                $wlCommission['commission_value'],
                $amount
            );
        }

        /** @var BillPaymentTransaction $txn */
        $txn = DB::transaction(function () use ($user, $data, $amount, $commission, $commissionAmount) {
            $wl = $this->whitelabelBillingGate->lockForDebit($user, $amount);

            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ((float) $lockedUser->wallet_balance < $amount) {
                $available = (float) $lockedUser->wallet_balance;
                throw new \RuntimeException("Insufficient wallet balance. Please recharge your wallet. Required: ₹{$amount}, Available: ₹{$available}");
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
                'commission_type' => $commission['commission_type'],
                'commission_value' => $commission['commission_value'],
                'commission_amount' => $commissionAmount,
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
            $this->whitelabelBillingGate->debit(
                $wl,
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

            DB::transaction(function () use ($txn, $user, $provider, $commissionAmount, $wlCommissionAmount) {
                $wl = $this->whitelabelBillingGate->lockForUpdate($user);

                $txn->update([
                    'status' => 'success',
                    'provider_txid' => isset($provider['txid']) ? (string) $provider['txid'] : null,
                    'utr' => $provider['utr'] ?? null,
                    'response_payload' => $provider,
                ]);

                /** @var User $lockedUser */
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

                if ($commissionAmount > 0) {
                    $lockedUser->creditWallet(
                        $commissionAmount,
                        "Bill payment commission earned for {$txn->opcode} (Order: {$txn->order_id})",
                        $txn
                    );
                    $lockedUser->addEarning($commissionAmount);
                }

                $this->whitelabelBillingGate->creditCommission(
                    $wl,
                    $wlCommissionAmount,
                    "Bill payment commission for {$txn->opcode} (Order: {$txn->order_id})",
                    $txn
                );
            });

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

    private function refundFetchFee(User $user, float $fee, string $service, string $orderid): void
    {
        DB::transaction(function () use ($user, $fee, $service, $orderid) {
            $wl = $this->whitelabelBillingGate->lockForUpdate($user);

            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->creditWallet(
                $fee,
                "Plan API fee refund ({$service}) order {$orderid}",
            );
            $this->whitelabelBillingGate->refund(
                $wl,
                $fee,
                "Plan API fee refund ({$service}) order {$orderid}",
            );
        });
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
            $user = User::query()->findOrFail($locked->user_id);
            $wl = $this->whitelabelBillingGate->lockForUpdate($user);

            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($locked->user_id);
            $lockedUser->creditWallet(
                (float) $locked->amount,
                "Refund for failed credit card bill pay (Order: {$locked->order_id})",
                $locked
            );
            $this->whitelabelBillingGate->refund(
                $wl,
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
