<?php

namespace App\Services\ProductApi;

use App\Enums\EnvironmentSlug;
use App\Exceptions\WhitelabelUnavailableException;
use App\Models\LeadStatusSnapshot;
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

    public const BILLABLE_LEAD_STATUS = 'approved';

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
        return $this->execute($user, function () use ($user) {
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
        return $this->execute($user, function () use ($user, $categoryId) {
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
        $productId = $data['product_id'];
        $categoryId = isset($data['category_id']) && $data['category_id'] !== null
            ? (int) $data['category_id']
            : null;
        $cardId = isset($data['card_id']) && $data['card_id'] !== null
            ? (int) $data['card_id']
            : null;

        return $this->execute($user, function () use ($user, $productId, $categoryId, $cardId) {
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
        $productId = $data['product_id'];
        $categoryId = isset($data['category_id']) && $data['category_id'] !== null
            ? (int) $data['category_id']
            : null;
        $requiredAmount = isset($data['required_amount']) && $data['required_amount'] !== null
            ? (float) $data['required_amount']
            : null;

        return $this->execute($user, function () use ($user, $productId, $categoryId, $requiredAmount) {
            return $this->isUatToken($user)
                ? $this->uatMock->createLead($productId)
                : $this->bankSathi->createLead($productId, $categoryId, $requiredAmount);
        });
    }

    /**
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    public function leadStatus(User $user, string $leadCode): array
    {
        $orderid = 'LS_'.strtoupper(Str::random(10));
        $service = self::SERVICE_LEAD_GENERATION;
        $access = $this->assertAccess($user, $service);

        if ($this->isUatToken($user)) {
            $provider = $this->uatMock->leadStatus($leadCode);

            if (! $this->bankSathi->isSuccess($provider)) {
                throw new \RuntimeException((string) ($provider['message'] ?? 'Request failed.'));
            }

            return [
                'provider' => $provider,
                'fee' => 0.0,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        }

        $provider = $this->bankSathi->leadStatus($leadCode);

        if (! $this->bankSathi->isSuccess($provider)) {
            throw new \RuntimeException((string) ($provider['message'] ?? 'Request failed.'));
        }

        $newStatus = $this->normalizeLeadStatus($provider['data'] ?? null);
        $fee = 0.0;

        DB::transaction(function () use ($user, $access, $leadCode, $newStatus, $service, $orderid, &$fee) {
            $isWhitelabelUser = (bool) $user->whitelabel_id;
            $wl = $isWhitelabelUser
                ? $this->whitelabelBillingGate->lockForUpdate($user)
                : null;

            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            $snapshot = LeadStatusSnapshot::query()->firstOrCreate(
                [
                    'user_id' => $lockedUser->id,
                    'lead_code' => $leadCode,
                ],
                [
                    'last_status' => null,
                ]
            );
            $snapshot = LeadStatusSnapshot::query()
                ->whereKey($snapshot->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $this->normalizeLeadStatus($snapshot->last_status);
            $shouldBill = $this->isApprovedStatus($newStatus) && ! $this->isApprovedStatus($oldStatus);

            if ($shouldBill) {
                $userFee = round((float) $access->per_call_fee, 2);
                $wlFee = 0.0;

                if ($isWhitelabelUser) {
                    $wlAccess = WhitelabelPlanApiAccess::resolveFor((int) $user->whitelabel_id, $service);

                    if (! $wlAccess || ! $wlAccess['status'] || $wl === null) {
                        throw new WhitelabelUnavailableException(
                            WhitelabelUnavailableException::REASON_SUSPENDED,
                            (int) $user->whitelabel_id
                        );
                    }

                    $this->whitelabelBillingGate->assertUsable($wl, $userFee);
                    $wlFee = round($wlAccess['per_call_fee'], 2);
                }

                $wlMargin = $isWhitelabelUser ? max(0, round($userFee - $wlFee, 2)) : 0.0;

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
                }

                if ($isWhitelabelUser && $wlMargin > 0) {
                    $this->whitelabelBillingGate->creditCommission(
                        $wl,
                        $wlMargin,
                        "Plan API margin ({$service}) order {$orderid}",
                    );
                }

                $snapshot->commissioned_at = now();
                $fee = $userFee;
            }

            $snapshot->last_status = $newStatus;
            $snapshot->save();
        });

        return [
            'provider' => $provider,
            'fee' => $fee,
            'wallet_balance' => (float) $user->fresh()->wallet_balance,
        ];
    }

    /**
     * @param  callable(): array<string, mixed>  $call
     * @return array{provider: array<string, mixed>, fee: float, wallet_balance: float}
     */
    private function execute(User $user, callable $call): array
    {
        $this->assertAccess($user, self::SERVICE_LEAD_GENERATION);

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

    private function assertAccess(User $user, string $service): UserPlanApiAccess
    {
        $access = UserPlanApiAccess::query()
            ->where('user_id', $user->id)
            ->where('service', $service)
            ->first();

        if (! $access || ! $access->isActive()) {
            throw new \RuntimeException('This API is not enabled for your account. Contact admin.');
        }

        if ($user->whitelabel_id) {
            $wlAccess = WhitelabelPlanApiAccess::resolveFor((int) $user->whitelabel_id, $service);

            if (! $wlAccess || ! $wlAccess['status']) {
                throw new WhitelabelUnavailableException(
                    WhitelabelUnavailableException::REASON_SUSPENDED,
                    (int) $user->whitelabel_id
                );
            }
        }

        return $access;
    }

    private function isApprovedStatus(?string $status): bool
    {
        return $this->normalizeLeadStatus($status) === self::BILLABLE_LEAD_STATUS;
    }

    private function normalizeLeadStatus(mixed $data): string
    {
        if (is_string($data) || $data === null) {
            $status = strtolower(trim((string) $data));

            return $status !== '' ? $status : 'pending';
        }

        if (! is_array($data)) {
            return 'pending';
        }

        $status = strtolower(trim((string) ($data['lead_status'] ?? '')));

        return $status !== '' ? $status : 'pending';
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
}
