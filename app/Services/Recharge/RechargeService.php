<?php

namespace App\Services\Recharge;

use App\Filament\Pages\ManageOperatorCommissions;
use App\Models\RechargeTransaction;
use App\Models\User;
use App\Models\UserOperatorCommission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RechargeService
{
    protected RoundpayService $roundpayService;

    public function __construct(RoundpayService $roundpayService)
    {
        $this->roundpayService = $roundpayService;
    }

    /**
     * Process a recharge request
     *
     * @param User $user The authenticated developer user
     * @param array{
     *     account_number: string,
     *     amount: float,
     *     operator_sp_key: int,
     *     operator_type: string,
     *     client_request_id?: string,
     *     geocode?: string,
     *     customer_number?: string,
     *     pincode?: string
     * } $data
     * @return RechargeTransaction
     * @throws \Exception
     */
    public function processRecharge(User $user, array $data): RechargeTransaction
    {
        $accountNumber = $data['account_number'];
        $amount = (float) $data['amount'];
        $spKey = (int) $data['operator_sp_key'];
        $type = strtolower($data['operator_type']); // mobile or dth
        $clientRequestId = $data['client_request_id'] ?? null;
        $geocode = $data['geocode'] ?? null;
        $customerNumber = $data['customer_number'] ?? null;
        $pincode = $data['pincode'] ?? null;

        // 1. Operator validation
        $operator = $this->findOperator($spKey, $type);
        if (!$operator) {
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

        if (!$isOperatorActive) {
            throw new \Exception("This operator is currently inactive or disabled for your account.");
        }

        // 3. Compute net amount (Debit full face value of the recharge amount)
        $commissionAmount = round(($amount * $commissionPercentage) / 100, 2);
        $netAmount = (float) $amount;

        // 4. Generate system API request ID
        // Format: RC_YYYYMMDDHIS_10RandomChars
        $apiRequestId = 'RC_' . date('YmdHis') . '_' . bin2hex(random_bytes(5));

        // 5. Debit wallet and log transaction inside a DB transaction with pessimistic locking
        /** @var RechargeTransaction $rechargeTxn */
        $rechargeTxn = DB::transaction(function () use ($user, $clientRequestId, $apiRequestId, $spKey, $type, $accountNumber, $amount, $commissionPercentage, $commissionAmount, $netAmount) {
            // Lock user row for update to prevent concurrent wallet adjustments
            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if ($lockedUser->wallet_balance < $netAmount) {
                $available = (float) $lockedUser->wallet_balance;
                throw new \Exception("Insufficient wallet balance. Required: ₹{$netAmount}, Available: ₹{$available}");
            }

            // Create recharge record
            $txn = RechargeTransaction::create([
                'user_id' => $lockedUser->id,
                'client_request_id' => $clientRequestId,
                'api_request_id' => $apiRequestId,
                'operator_sp_key' => $spKey,
                'operator_type' => $type,
                'account_number' => $accountNumber,
                'amount' => $amount,
                'commission_percentage' => $commissionPercentage,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
                'status' => 'pending',
            ]);

            // Debit wallet
            $lockedUser->debitWallet($netAmount, "Recharge debit for {$accountNumber} (Amount: ₹{$amount})", $txn);

            return $txn;
        });

        // 6. Execute external API request outside DB transaction
        Log::info("Executing Roundpay call for transaction: {$rechargeTxn->id} (APIRequestID: {$apiRequestId})");

        $roundpayResult = $this->roundpayService->executeRecharge(
            $apiRequestId,
            $accountNumber,
            $amount,
            (string) $spKey,
            $geocode,
            $customerNumber,
            $pincode
        );

        // 7. Update transaction status and handle reversal/refund in case of failure
        if ($roundpayResult['status'] === 'success') {
            DB::transaction(function () use ($rechargeTxn, $user, $roundpayResult) {
                $rechargeTxn->update([
                    'status' => 'success',
                    'rpid' => $roundpayResult['rpid'] ?? null,
                    'opid' => $roundpayResult['opid'] ?? null,
                    'error_code' => $roundpayResult['errorCode'] ?? '200',
                ]);

                // Credit commission to user's wallet and increment lifetime earnings
                if ($rechargeTxn->commission_amount > 0) {
                    /** @var User $lockedUser */
                    $lockedUser = User::query()->lockForUpdate()->find($user->id);
                    $lockedUser->creditWallet(
                        $rechargeTxn->commission_amount,
                        "Recharge commission earned for {$rechargeTxn->account_number} (Amount: ₹{$rechargeTxn->amount})",
                        $rechargeTxn
                    );
                    $lockedUser->addEarning($rechargeTxn->commission_amount);
                }
            });
        } elseif ($roundpayResult['status'] === 'pending') {
            $rechargeTxn->update([
                'status' => 'pending',
                'rpid' => $roundpayResult['rpid'] ?? null,
                'error_code' => $roundpayResult['errorCode'] ?? '200',
            ]);
        } else {
            // FAILED - Process instant wallet refund/reversal
            DB::transaction(function () use ($rechargeTxn, $user, $netAmount, $roundpayResult, $accountNumber) {
                $rechargeTxn->update([
                    'status' => 'failed',
                    'error_code' => $roundpayResult['errorCode'] ?? '500',
                    'error_message' => $roundpayResult['msg'] ?? 'Transaction Failed',
                ]);

                // Refund the user's wallet
                /** @var User $lockedUser */
                $lockedUser = User::query()->lockForUpdate()->find($user->id);
                $lockedUser->creditWallet($netAmount, "Reversal/Refund for failed recharge on {$accountNumber}", $rechargeTxn);
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
            if ((int)$operator['sp_key'] === $spKey && strtolower($operator['type']) === $type) {
                return $operator;
            }
        }
        return null;
    }
}
