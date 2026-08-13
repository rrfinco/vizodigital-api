<?php

namespace App\Services\ProductApi;

use App\Enums\EnvironmentSlug;
use App\Exceptions\WhitelabelUnavailableException;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Models\WhitelabelPlanApiAccess;
use App\Services\BankSathi\BankSathiService;
use App\Services\Whitelabel\WhitelabelBillingGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductApiService
{
    public const SERVICE_LEAD_GENERATION = 'lead_generation';

    public function __construct(
        private readonly BankSathiService $bankSathi,
        private readonly ProductApiUatMock $uatMock,
        private readonly WhitelabelBillingGate $whitelabelBillingGate,
    ) {}

    /**
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function productCategories(User $user): array
    {
        $orderid = 'PC_'.strtoupper(Str::random(10));

        return $this->execute($user, $orderid, function () use ($user) {
            return $this->isUatToken($user)
                ? $this->uatMock->allProductCategories()
                : $this->bankSathi->allProductCategories();
        });
    }

    /**
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function productsByCategory(User $user, int $categoryId): array
    {
        $orderid = 'PB_'.strtoupper(Str::random(10));

        return $this->execute($user, $orderid, function () use ($user, $categoryId) {
            return $this->isUatToken($user)
                ? $this->uatMock->productsByCategory($categoryId)
                : $this->bankSathi->productsByCategory($categoryId);
        });
    }

    /**
     * @param  array{product_id: string, category_id?: int|null, card_id?: int|null}  $data
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function productDetails(User $user, array $data): array
    {
        $orderid = 'PD_'.strtoupper(Str::random(10));
        $productId = $data['product_id'];
        $categoryId = isset($data['category_id']) && $data['category_id'] !== null
            ? (int) $data['category_id']
            : null;
        $cardId = isset($data['card_id']) && $data['card_id'] !== null
            ? (int) $data['card_id']
            : null;

        return $this->execute($user, $orderid, function () use ($user, $productId, $categoryId, $cardId) {
            return $this->isUatToken($user)
                ? $this->uatMock->productDetails($productId)
                : $this->bankSathi->productDetails($productId, $categoryId, $cardId);
        });
    }

    /**
     * @param  array{product_id: string, category_id?: int|null, required_amount?: float|int|null}  $data
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function createLead(User $user, array $data): array
    {
        $orderid = 'LD_'.strtoupper(Str::random(10));
        $productId = $data['product_id'];
        $categoryId = isset($data['category_id']) && $data['category_id'] !== null
            ? (int) $data['category_id']
            : null;
        $requiredAmount = isset($data['required_amount']) && $data['required_amount'] !== null
            ? (float) $data['required_amount']
            : null;

        return $this->execute($user, $orderid, function () use ($user, $productId, $categoryId, $requiredAmount) {
            return $this->isUatToken($user)
                ? $this->uatMock->createLead($productId)
                : $this->bankSathi->createLead($productId, $categoryId, $requiredAmount);
        }, chargePerLead: true);
    }

    /**
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function leadStatus(User $user, string $leadCode): array
    {
        $orderid = 'LS_'.strtoupper(Str::random(10));

        return $this->execute($user, $orderid, function () use ($user, $leadCode) {
            return $this->isUatToken($user)
                ? $this->uatMock->leadStatus($leadCode)
                : $this->bankSathi->leadStatus($leadCode);
        });
    }

    /**
     * @param  callable(): array<string, mixed>  $call
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    private function execute(User $user, string $orderid, callable $call, bool $chargePerLead = false): array
    {
        $service = self::SERVICE_LEAD_GENERATION;

        $access = UserPlanApiAccess::query()
            ->where('user_id', $user->id)
            ->where('service', $service)
            ->first();

        if (! $access || ! $access->isActive()) {
            throw new \RuntimeException('This API is not enabled for your account. Contact admin.');
        }

        $isWhitelabelUser = (bool) $user->whitelabel_id;
        $wlFee = 0.0;
        $wlMargin = 0.0;

        if ($isWhitelabelUser) {
            $wlAccess = WhitelabelPlanApiAccess::resolveFor((int) $user->whitelabel_id, $service);

            if (! $wlAccess || ! $wlAccess['status']) {
                throw new WhitelabelUnavailableException(
                    WhitelabelUnavailableException::REASON_SUSPENDED,
                    (int) $user->whitelabel_id
                );
            }

            $wlFee = round($wlAccess['per_call_fee'], 2);
        }

        if ($this->isUatToken($user) || ! $chargePerLead) {
            $provider = $call();

            if (! $this->bankSathi->isSuccess($provider)) {
                throw new \RuntimeException((string) ($provider['message'] ?? 'Request failed.'));
            }

            return [
                'provider' => $provider,
                'fee' => 0.0,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        }

        $userFee = round((float) $access->per_call_fee, 2);
        $wlMargin = $isWhitelabelUser ? max(0, round($userFee - $wlFee, 2)) : 0.0;

        $debited = false;
        $refunded = false;

        DB::transaction(function () use ($user, $userFee, $service, $orderid, &$debited) {
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
                    "Lead fee ({$service}) order {$orderid}",
                );
                $this->whitelabelBillingGate->debit(
                    $wl,
                    $userFee,
                    "Lead fee ({$service}) order {$orderid}",
                );
                $debited = true;
            }
        });

        try {
            $provider = $call();

            if (! $this->bankSathi->isSuccess($provider)) {
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
                        "Lead margin ({$service}) order {$orderid}",
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
                "Lead fee refund ({$service}) order {$orderid}",
            );
            $this->whitelabelBillingGate->refund(
                $wl,
                $fee,
                "Lead fee refund ({$service}) order {$orderid}",
            );
        });
    }
}
