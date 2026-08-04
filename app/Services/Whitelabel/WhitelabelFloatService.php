<?php

namespace App\Services\Whitelabel;

use App\Models\User;
use App\Models\Whitelabel;
use App\Models\WhitelabelFloatRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhitelabelFloatService
{
    public function requestTopUp(
        Whitelabel $whitelabel,
        User $requester,
        float $amount,
        string $utr,
        ?string $proofPath = null,
    ): WhitelabelFloatRequest {
        if ((int) $requester->whitelabel_id !== (int) $whitelabel->id) {
            throw new \RuntimeException('You can only request float for your own white-label.');
        }

        $amount = abs($amount);
        if ($amount < 1) {
            throw new \InvalidArgumentException('Amount must be at least ₹1.');
        }

        return WhitelabelFloatRequest::query()->create([
            'whitelabel_id' => $whitelabel->id,
            'requested_by' => $requester->id,
            'amount' => $amount,
            'method' => WhitelabelFloatRequest::METHOD_BANK_TRANSFER,
            'status' => WhitelabelFloatRequest::STATUS_PENDING,
            'utr' => $utr,
            'proof_path' => $proofPath,
        ]);
    }

    public function approve(WhitelabelFloatRequest $request, User $admin, ?string $notes = null): WhitelabelFloatRequest
    {
        return DB::transaction(function () use ($request, $admin, $notes) {
            /** @var WhitelabelFloatRequest $locked */
            $locked = WhitelabelFloatRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new \RuntimeException('This float request has already been processed.');
            }

            /** @var Whitelabel $wl */
            $wl = Whitelabel::query()->lockForUpdate()->findOrFail($locked->whitelabel_id);

            $locked->update([
                'status' => WhitelabelFloatRequest::STATUS_APPROVED,
                'admin_notes' => $notes,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $wl->creditWallet(
                (float) $locked->amount,
                'Float top-up approved (UTR: '.($locked->utr ?: 'n/a').')',
                $locked
            );

            Log::info('Approved whitelabel float request', [
                'float_request_id' => $locked->id,
                'whitelabel_id' => $wl->id,
                'amount' => (float) $locked->amount,
                'admin_id' => $admin->id,
            ]);

            return $locked->fresh(['whitelabel', 'requester', 'reviewedBy']);
        });
    }

    public function reject(WhitelabelFloatRequest $request, User $admin, string $reason): WhitelabelFloatRequest
    {
        return DB::transaction(function () use ($request, $admin, $reason) {
            /** @var WhitelabelFloatRequest $locked */
            $locked = WhitelabelFloatRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new \RuntimeException('This float request has already been processed.');
            }

            $locked->update([
                'status' => WhitelabelFloatRequest::STATUS_REJECTED,
                'admin_notes' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            Log::info('Rejected whitelabel float request', [
                'float_request_id' => $locked->id,
                'whitelabel_id' => $locked->whitelabel_id,
                'admin_id' => $admin->id,
            ]);

            return $locked->fresh(['whitelabel', 'requester', 'reviewedBy']);
        });
    }

    public function adjustFloat(Whitelabel $whitelabel, User $admin, float $amount, string $reason): Whitelabel
    {
        return DB::transaction(function () use ($whitelabel, $admin, $amount, $reason) {
            /** @var Whitelabel $wl */
            $wl = Whitelabel::query()->lockForUpdate()->findOrFail($whitelabel->id);

            $description = 'Manual float adjust by admin #'.$admin->id.': '.$reason;

            if ($amount >= 0) {
                $wl->creditWallet($amount, $description);
            } else {
                $wl->debitWallet(abs($amount), $description);
            }

            return $wl->fresh();
        });
    }
}
