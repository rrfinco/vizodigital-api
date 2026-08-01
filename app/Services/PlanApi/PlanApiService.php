<?php

namespace App\Services\PlanApi;

use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Services\EkycHub\EkycHubService;
use Illuminate\Support\Facades\DB;

class PlanApiService
{
    public const SERVICE_OPERATOR_FETCH = 'operator_fetch';

    public const SERVICE_OPERATOR_PLAN_FETCH = 'operator_plan_fetch';

    public const SERVICE_DTH_PLAN_FETCH = 'dth_plan_fetch';

    public const SERVICE_DTH_INFO = 'dth_info';

    public function __construct(
        private readonly EkycHubService $ekycHub
    ) {}

    /**
     * @param  array{mobile: string, orderid: string}  $data
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function operatorFetch(User $user, array $data): array
    {
        return $this->execute($user, self::SERVICE_OPERATOR_FETCH, $data['orderid'], function () use ($data) {
            return $this->ekycHub->operatorFetch($data);
        });
    }

    /**
     * @param  array{mobile: string, opcode: string, circle: string, orderid: string}  $data
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function operatorPlanFetch(User $user, array $data): array
    {
        return $this->execute($user, self::SERVICE_OPERATOR_PLAN_FETCH, $data['orderid'], function () use ($data) {
            return $this->ekycHub->operatorPlanFetch($data);
        });
    }

    /**
     * @param  array{dth_number: string, opcode: string, orderid: string}  $data
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function dthPlanFetch(User $user, array $data): array
    {
        return $this->execute($user, self::SERVICE_DTH_PLAN_FETCH, $data['orderid'], function () use ($data) {
            return $this->ekycHub->dthPlanFetch($data);
        });
    }

    /**
     * @param  array{dth_number: string, opcode: string, orderid: string}  $data
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function dthInfo(User $user, array $data): array
    {
        return $this->execute($user, self::SERVICE_DTH_INFO, $data['orderid'], function () use ($data) {
            return $this->ekycHub->dthInfo($data);
        });
    }

    /**
     * @param  callable(): array<string, mixed>  $call
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    private function execute(User $user, string $service, string $orderid, callable $call): array
    {
        $access = UserPlanApiAccess::query()
            ->where('user_id', $user->id)
            ->where('service', $service)
            ->first();

        if (! $access || ! $access->isActive()) {
            throw new \RuntimeException('This API is not enabled for your account. Contact admin.');
        }

        $fee = round((float) $access->per_call_fee, 2);
        $debited = false;
        $refunded = false;

        if ($fee > 0) {
            DB::transaction(function () use ($user, $fee, $service, $orderid, &$debited) {
                /** @var User $lockedUser */
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

                if ((float) $lockedUser->wallet_balance < $fee) {
                    $available = (float) $lockedUser->wallet_balance;
                    throw new \RuntimeException("Insufficient wallet balance. Required: ₹{$fee}, Available: ₹{$available}");
                }

                $lockedUser->debitWallet(
                    $fee,
                    "Plan API fee ({$service}) order {$orderid}",
                );
                $debited = true;
            });
        }

        try {
            $provider = $call();

            if (! $this->ekycHub->isSuccess($provider)) {
                $message = (string) ($provider['message'] ?? 'Request failed.');

                if ($debited) {
                    $this->refundFee($user, $fee, $service, $orderid);
                    $refunded = true;
                }

                throw new \RuntimeException($message);
            }

            return [
                'provider' => $provider,
                'fee' => $fee,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        } catch (\Throwable $e) {
            if ($debited && ! $refunded) {
                $this->refundFee($user, $fee, $service, $orderid);
            }

            throw $e;
        }
    }

    private function refundFee(User $user, float $fee, string $service, string $orderid): void
    {
        DB::transaction(function () use ($user, $fee, $service, $orderid) {
            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->creditWallet(
                $fee,
                "Plan API fee refund ({$service}) order {$orderid}",
            );
        });
    }
}
