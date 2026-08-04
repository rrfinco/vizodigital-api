<?php

namespace App\Services\PlanApi;

use App\Enums\EnvironmentSlug;
use App\Exceptions\WhitelabelUnavailableException;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Models\WhitelabelPlanApiAccess;
use App\Services\EkycHub\EkycHubService;
use App\Services\Whitelabel\WhitelabelBillingGate;
use Illuminate\Support\Facades\DB;

class PlanApiService
{
    public const SERVICE_OPERATOR_FETCH = 'operator_fetch';

    public const SERVICE_OPERATOR_PLAN_FETCH = 'operator_plan_fetch';

    public const SERVICE_DTH_PLAN_FETCH = 'dth_plan_fetch';

    public const SERVICE_DTH_INFO = 'dth_info';

    public function __construct(
        private readonly EkycHubService $ekycHub,
        private readonly PlanApiUatMock $uatMock,
        private readonly WhitelabelBillingGate $whitelabelBillingGate,
    ) {}

    /**
     * @param  array{mobile: string, orderid: string}  $data
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function operatorFetch(User $user, array $data): array
    {
        return $this->execute($user, self::SERVICE_OPERATOR_FETCH, $data['orderid'], function () use ($user, $data) {
            return $this->isUatToken($user)
                ? $this->uatMock->operatorFetch($data)
                : $this->ekycHub->operatorFetch($data);
        });
    }

    /**
     * @param  array{mobile: string, opcode: string, circle: string, orderid: string}  $data
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function operatorPlanFetch(User $user, array $data): array
    {
        return $this->execute($user, self::SERVICE_OPERATOR_PLAN_FETCH, $data['orderid'], function () use ($user, $data) {
            return $this->isUatToken($user)
                ? $this->uatMock->operatorPlanFetch($data)
                : $this->ekycHub->operatorPlanFetch($data);
        });
    }

    /**
     * @param  array{dth_number: string, opcode: string, orderid: string}  $data
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function dthPlanFetch(User $user, array $data): array
    {
        return $this->execute($user, self::SERVICE_DTH_PLAN_FETCH, $data['orderid'], function () use ($user, $data) {
            return $this->isUatToken($user)
                ? $this->uatMock->dthPlanFetch($data)
                : $this->ekycHub->dthPlanFetch($data);
        });
    }

    /**
     * @param  array{dth_number: string, opcode: string, orderid: string}  $data
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function dthInfo(User $user, array $data): array
    {
        return $this->execute($user, self::SERVICE_DTH_INFO, $data['orderid'], function () use ($user, $data) {
            return $this->isUatToken($user)
                ? $this->uatMock->dthInfo($data)
                : $this->ekycHub->dthInfo($data);
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

        // UAT tokens get sample data only — no aggregator hit, no wallet/float debit.
        if ($this->isUatToken($user)) {
            $provider = $call();

            if (! $this->ekycHub->isSuccess($provider)) {
                throw new \RuntimeException((string) ($provider['message'] ?? 'Request failed.'));
            }

            return [
                'provider' => $provider,
                'fee' => 0.0,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
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

        try {
            $provider = $call();

            if (! $this->ekycHub->isSuccess($provider)) {
                $message = (string) ($provider['message'] ?? 'Request failed.');

                if ($debited) {
                    $this->refundFee($user, $userFee, $service, $orderid);
                    $refunded = true;
                }

                throw new \RuntimeException($message);
            }

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
                'provider' => $provider,
                'fee' => $userFee,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        } catch (\Throwable $e) {
            if ($debited && ! $refunded) {
                $this->refundFee($user, $userFee, $service, $orderid);
            }

            throw $e;
        }
    }

    private function isUatToken(User $user): bool
    {
        $token = $user->currentAccessToken();

        if ($token === null) {
            return false;
        }

        // Prefer the abilities list on real PersonalAccessToken rows. Sanctum's
        // tokenCan() treats "*" as matching every ability, so production tokens
        // issued as ["*", "environment:production"] would look like UAT.
        $abilities = $token->abilities ?? null;

        if (is_array($abilities)) {
            if (in_array(EnvironmentSlug::Production->tokenAbility(), $abilities, true)) {
                return false;
            }

            return in_array(EnvironmentSlug::Uat->tokenAbility(), $abilities, true);
        }

        return false;
    }

    private function refundFee(User $user, float $fee, string $service, string $orderid): void
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
}
