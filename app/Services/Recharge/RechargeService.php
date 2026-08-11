<?php

namespace App\Services\Recharge;

use App\Enums\RechargeProvider;
use App\Exceptions\WhitelabelUnavailableException;
use App\Filament\Pages\ManageOperatorCommissions;
use App\Models\RechargeTransaction;
use App\Models\User;
use App\Models\UserOperatorCommission;
use App\Models\WhitelabelOperatorCommission;
use App\Services\Whitelabel\WhitelabelBillingGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RechargeService
{
    public function __construct(
        protected RoundpayService $roundpayService,
        protected MokshiqService $mokshiqService,
        protected RechargeProviderResolver $providerResolver,
        protected WhitelabelBillingGate $whitelabelBillingGate,
    ) {}

    /**
     * Process a recharge request
     *
     * @param  array{
     *     account_number: string,
     *     amount: float,
     *     operator_sp_key: int,
     *     operator_type: string,
     *     client_request_id?: string,
     *     circle?: string|null,
     *     geocode?: string,
     *     customer_number?: string,
     *     pincode?: string
     * }  $data
     *
     * @throws \Exception
     * @throws WhitelabelUnavailableException
     */
    public function processRecharge(User $user, array $data): RechargeTransaction
    {
        $accountNumber = $data['account_number'];
        $amount = (float) $data['amount'];
        $spKey = (int) $data['operator_sp_key'];
        $type = strtolower($data['operator_type']); // mobile or dth
        $clientRequestId = isset($data['client_request_id']) ? trim((string) $data['client_request_id']) : null;
        $clientRequestId = $clientRequestId === '' ? null : $clientRequestId;
        $circle = isset($data['circle']) ? trim((string) $data['circle']) : null;
        $circle = $circle === '' ? null : $circle;
        $geocode = $data['geocode'] ?? null;
        $customerNumber = $data['customer_number'] ?? null;
        $pincode = $data['pincode'] ?? null;

        $provider = $this->providerResolver->forUser($user);

        if ($provider === RechargeProvider::Mokshiq && $type === 'mobile' && $circle === null) {
            throw ValidationException::withMessages([
                'circle' => 'Circle is required for Mokshiq recharges. Call operator/plan fetch first, then pass the circle name.',
            ]);
        }

        $mokshiqOperator = null;
        if ($provider === RechargeProvider::Mokshiq) {
            $mokshiqOperator = MokshiqOperatorMap::operatorName($spKey);
            if ($mokshiqOperator === null) {
                throw ValidationException::withMessages([
                    'operator_sp_key' => "Operator SPKey [{$spKey}] is not mapped for Mokshiq.",
                ]);
            }
        }

        if ($clientRequestId !== null) {
            $duplicate = RechargeTransaction::query()
                ->where('user_id', $user->id)
                ->where('client_request_id', $clientRequestId)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'client_request_id' => 'This client_request_id was already used. Provide a unique order ID.',
                ]);
            }
        }

        // 1. Operator validation
        $operator = $this->findOperator($spKey, $type);
        if (! $operator) {
            throw new \Exception("Invalid operator SPKey [{$spKey}] for type [{$type}].");
        }

        // 2. Fetch custom user commission config
        $commissionConfig = UserOperatorCommission::query()
            ->where('user_id', $user->id)
            ->where('operator_type', $type)
            ->where('operator_sp_key', $spKey)
            ->first();

        $commissionPercentage = $operator['default_commission'];
        $isOperatorActive = $operator['default_status'] === 'Active';

        if ($commissionConfig) {
            $commissionPercentage = $commissionConfig->commission_percentage;
            $isOperatorActive = (bool) $commissionConfig->status;
        }

        if (! $isOperatorActive) {
            throw new \Exception('This operator is currently inactive or disabled for your account.');
        }

        // 3. Compute amounts (debit full face; commission credited on success)
        $commissionAmount = round(($amount * $commissionPercentage) / 100, 2);
        $netAmount = (float) $amount;

        $wlCommissionAmount = 0.0;
        if ($user->whitelabel_id) {
            $wlConfig = WhitelabelOperatorCommission::resolveFor(
                (int) $user->whitelabel_id,
                $type,
                $spKey,
                (float) $operator['default_commission']
            );

            if (! $wlConfig['status']) {
                throw new WhitelabelUnavailableException(
                    WhitelabelUnavailableException::REASON_SUSPENDED,
                    (int) $user->whitelabel_id
                );
            }

            $wlCommissionAmount = round(($amount * $wlConfig['commission_percentage']) / 100, 2);
        }

        // 4. Generate system API request ID
        // Roundpay rejects APIRequestID longer than 25 characters.
        $apiRequestId = 'RC'.date('ymdHis').bin2hex(random_bytes(4));

        // 5. Debit developer + WL float (lock WL then user)
        /** @var RechargeTransaction $rechargeTxn */
        $rechargeTxn = DB::transaction(function () use (
            $user,
            $provider,
            $clientRequestId,
            $apiRequestId,
            $spKey,
            $type,
            $accountNumber,
            $circle,
            $amount,
            $commissionPercentage,
            $commissionAmount,
            $netAmount
        ) {
            $wl = $this->whitelabelBillingGate->lockForDebit($user, $netAmount);

            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->wallet_balance < $netAmount) {
                $available = (float) $lockedUser->wallet_balance;
                throw new \Exception("Insufficient wallet balance. Please recharge your wallet. Required: ₹{$netAmount}, Available: ₹{$available}");
            }

            $txn = RechargeTransaction::create([
                'user_id' => $lockedUser->id,
                'provider' => $provider,
                'client_request_id' => $clientRequestId,
                'api_request_id' => $apiRequestId,
                'operator_sp_key' => $spKey,
                'operator_type' => $type,
                'account_number' => $accountNumber,
                'circle' => $circle,
                'amount' => $amount,
                'commission_percentage' => $commissionPercentage,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
                'status' => 'pending',
            ]);

            $lockedUser->debitWallet($netAmount, "Recharge debit for {$accountNumber} (Amount: ₹{$amount})", $txn);
            $this->whitelabelBillingGate->debit(
                $wl,
                $netAmount,
                "Recharge debit for {$accountNumber} (Amount: ₹{$amount})",
                $txn
            );

            return $txn;
        });

        // 6. Execute external API request outside DB transaction
        Log::info("Executing {$provider->value} call for transaction: {$rechargeTxn->id} (APIRequestID: {$apiRequestId})");

        if ($provider === RechargeProvider::Mokshiq) {
            if ($type === 'mobile') {
                $providerResult = $this->mokshiqService->createMobileRecharge([
                    'operator' => (string) $mokshiqOperator,
                    'number' => $accountNumber,
                    'amount' => $amount,
                    'circle' => (string) $circle,
                ]);
            } else {
                $providerResult = $this->mokshiqService->createDthRecharge([
                    'operator' => (string) $mokshiqOperator,
                    'number' => $accountNumber,
                    'amount' => $amount,
                ]);
            }
        } else {
            $providerResult = $this->roundpayService->executeRecharge(
                $apiRequestId,
                $accountNumber,
                $amount,
                (string) $spKey,
                $geocode,
                $customerNumber,
                $pincode
            );
        }

        // 7. Update status + commission / refund
        if ($providerResult['status'] === 'success') {
            DB::transaction(function () use ($rechargeTxn, $user, $providerResult, $wlCommissionAmount) {
                $wl = $this->whitelabelBillingGate->lockForUpdate($user);

                $rechargeTxn->update([
                    'status' => 'success',
                    'rpid' => $providerResult['rpid'] ?? null,
                    'opid' => $providerResult['opid'] ?? null,
                    'error_code' => $providerResult['errorCode'] ?? '200',
                ]);

                /** @var User $lockedUser */
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

                if ($rechargeTxn->commission_amount > 0) {
                    $lockedUser->creditWallet(
                        $rechargeTxn->commission_amount,
                        "Recharge commission earned for {$rechargeTxn->account_number} (Amount: ₹{$rechargeTxn->amount})",
                        $rechargeTxn
                    );
                    $lockedUser->addEarning($rechargeTxn->commission_amount);
                }

                $this->whitelabelBillingGate->creditCommission(
                    $wl,
                    $wlCommissionAmount,
                    "Recharge commission for {$rechargeTxn->account_number} (Amount: ₹{$rechargeTxn->amount})",
                    $rechargeTxn
                );
            });
        } elseif ($providerResult['status'] === 'pending') {
            $rechargeTxn->update([
                'status' => 'pending',
                'rpid' => $providerResult['rpid'] ?? null,
                'error_code' => $providerResult['errorCode'] ?? '200',
            ]);
        } else {
            DB::transaction(function () use ($rechargeTxn, $user, $netAmount, $providerResult, $accountNumber) {
                $wl = $this->whitelabelBillingGate->lockForUpdate($user);

                $rechargeTxn->update([
                    'status' => 'failed',
                    'error_code' => $providerResult['errorCode'] ?? '500',
                    'error_message' => $providerResult['msg'] ?? 'Transaction Failed',
                ]);

                /** @var User $lockedUser */
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
                $lockedUser->creditWallet($netAmount, "Reversal/Refund for failed recharge on {$accountNumber}", $rechargeTxn);
                $this->whitelabelBillingGate->refund(
                    $wl,
                    $netAmount,
                    "Reversal/Refund for failed recharge on {$accountNumber}",
                    $rechargeTxn
                );
            });
        }

        return $rechargeTxn->fresh();
    }

    /**
     * Find operator by SPKey and Type
     */
    protected function findOperator(int $spKey, string $type): ?array
    {
        foreach (ManageOperatorCommissions::OPERATORS as $operator) {
            if ((int) $operator['sp_key'] === $spKey && strtolower($operator['type']) === $type) {
                return $operator;
            }
        }

        return null;
    }
}
