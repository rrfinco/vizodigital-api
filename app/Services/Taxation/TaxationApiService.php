<?php

namespace App\Services\Taxation;

use App\Exceptions\WhitelabelUnavailableException;
use App\Models\TaxationClient;
use App\Models\TaxationDocument;
use App\Models\TaxationOrder;
use App\Models\TaxationService;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Models\WhitelabelPlanApiAccess;
use App\Services\Whitelabel\WhitelabelBillingGate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaxationApiService
{
    public const SERVICE = TaxationCatalog::SERVICE_ACCESS_KEY;

    public function __construct(
        protected WhitelabelBillingGate $whitelabelBillingGate,
    ) {}

    public function assertAccess(User $user): UserPlanApiAccess
    {
        $access = UserPlanApiAccess::query()
            ->where('user_id', $user->id)
            ->where('service', self::SERVICE)
            ->first();

        if (! $access || ! $access->isActive()) {
            throw new \RuntimeException('This API is not enabled for your account. Contact admin.');
        }

        if ($user->whitelabel_id) {
            $wlAccess = WhitelabelPlanApiAccess::resolveFor((int) $user->whitelabel_id, self::SERVICE);

            if (! $wlAccess || ! $wlAccess['status']) {
                throw new WhitelabelUnavailableException(
                    WhitelabelUnavailableException::REASON_SUSPENDED,
                    (int) $user->whitelabel_id
                );
            }
        }

        return $access;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createClient(User $user, array $data): TaxationClient
    {
        $this->assertAccess($user);

        $clientRequestId = $this->nullableRequestId($data['client_request_id'] ?? null);

        if ($clientRequestId !== null) {
            $duplicate = TaxationClient::query()
                ->where('user_id', $user->id)
                ->where('client_request_id', $clientRequestId)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'client_request_id' => 'This client_request_id was already used. Provide a unique order ID.',
                ]);
            }
        }

        return TaxationClient::query()->create([
            'user_id' => $user->id,
            'whitelabel_id' => $user->whitelabel_id,
            'client_request_id' => $clientRequestId,
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'pan' => strtoupper((string) $data['pan']),
            'aadhaar' => $data['aadhaar'],
            'residence_address' => $data['residence_address'],
            'residence_city' => $data['residence_city'],
            'residence_pincode' => $data['residence_pincode'],
            'residence_state' => $data['residence_state'],
            'office_address' => $data['office_address'],
            'office_city' => $data['office_city'],
            'office_pincode' => $data['office_pincode'],
            'office_state' => $data['office_state'],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listClients(User $user): array
    {
        $this->assertAccess($user);

        return TaxationClient::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (TaxationClient $client) => $this->clientPayload($client))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listServices(User $user, ?string $categorySlug = null): array
    {
        $this->assertAccess($user);

        $query = TaxationService::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($categorySlug !== null && $categorySlug !== '') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        $services = $query->get();

        return $services
            ->map(fn (TaxationService $service) => [
                'service_id' => $service->id,
                'category' => $service->category?->name,
                'category_slug' => $service->category?->slug,
                'name' => $service->name,
                'price' => (float) $service->price,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{client_id: int, service_id: int, client_request_id?: string|null}  $data
     */
    public function createOrder(User $user, array $data): TaxationOrder
    {
        $this->assertAccess($user);

        $clientRequestId = $this->nullableRequestId($data['client_request_id'] ?? null);

        if ($clientRequestId !== null) {
            $duplicate = TaxationOrder::query()
                ->where('user_id', $user->id)
                ->where('client_request_id', $clientRequestId)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'client_request_id' => 'This client_request_id was already used. Provide a unique order ID.',
                ]);
            }
        }

        $client = TaxationClient::query()
            ->where('user_id', $user->id)
            ->find($data['client_id']);

        if (! $client) {
            throw ValidationException::withMessages([
                'client_id' => 'Client not found. Create the client first and pass client_id.',
            ]);
        }

        $service = TaxationService::query()->find($data['service_id']);
        if (! $service || ! $service->is_active) {
            throw ValidationException::withMessages([
                'service_id' => 'Invalid or inactive service_id.',
            ]);
        }

        $amount = (float) $service->price;
        $apiRequestId = 'TX'.date('ymdHis').bin2hex(random_bytes(4));

        return DB::transaction(function () use (
            $user,
            $client,
            $service,
            $clientRequestId,
            $apiRequestId,
            $amount,
        ) {
            $wl = $this->whitelabelBillingGate->lockForDebit($user, $amount);

            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ((float) $lockedUser->wallet_balance < $amount) {
                $available = (float) $lockedUser->wallet_balance;
                throw new \RuntimeException("Insufficient wallet balance. Please recharge your wallet. Required: ₹{$amount}, Available: ₹{$available}");
            }

            $order = TaxationOrder::query()->create([
                'user_id' => $lockedUser->id,
                'whitelabel_id' => $lockedUser->whitelabel_id,
                'taxation_client_id' => $client->id,
                'taxation_service_id' => $service->id,
                'service_name' => $service->name,
                'amount' => $amount,
                'commission_percentage' => 0,
                'commission_amount' => 0,
                'whitelabel_commission_amount' => 0,
                'status' => TaxationOrder::STATUS_PENDING,
                'documents_status' => TaxationOrder::DOCUMENTS_PENDING,
                'client_request_id' => $clientRequestId,
                'api_request_id' => $apiRequestId,
            ]);

            $lockedUser->debitWallet($amount, "Taxation order debit for {$service->name} (Amount: ₹{$amount})", $order);
            $this->whitelabelBillingGate->debit(
                $wl,
                $amount,
                "Taxation order debit for {$service->name} (Amount: ₹{$amount})",
                $order
            );

            return $order->fresh(['client', 'service']) ?? $order;
        });
    }

    public function markStatus(TaxationOrder $order, string $status): TaxationOrder
    {
        $allowed = [
            TaxationOrder::STATUS_PENDING,
            TaxationOrder::STATUS_PROCESSING,
            TaxationOrder::STATUS_COMPLETED,
            TaxationOrder::STATUS_CANCELLED,
        ];

        if (! in_array($status, $allowed, true)) {
            throw new \RuntimeException('Invalid order status.');
        }

        if ($order->status === TaxationOrder::STATUS_CANCELLED) {
            throw new \RuntimeException('Cancelled orders cannot be updated.');
        }

        if ($status === TaxationOrder::STATUS_CANCELLED) {
            return $this->cancelOrder($order);
        }

        $order->update(['status' => $status]);

        return $order->fresh() ?? $order;
    }

    /**
     * @param  list<array{type: string, file: UploadedFile}>  $documents
     */
    public function uploadDocuments(User $user, int $orderId, array $documents): TaxationOrder
    {
        $this->assertAccess($user);

        if ($documents === []) {
            throw ValidationException::withMessages([
                'documents' => 'Upload at least one document after payment confirmation.',
            ]);
        }

        $order = $this->findOrder($user, $orderId);

        if (! $order->canReceiveDocuments()) {
            throw new \RuntimeException('Documents cannot be uploaded for this order.');
        }

        $allowedTypes = array_keys(TaxationDocument::TYPES);

        return DB::transaction(function () use ($order, $documents, $allowedTypes) {
            foreach ($documents as $index => $row) {
                $type = (string) ($row['type'] ?? '');
                if (! in_array($type, $allowedTypes, true)) {
                    throw ValidationException::withMessages([
                        "documents.{$index}.type" => 'Invalid document type.',
                    ]);
                }

                /** @var UploadedFile $file */
                $file = $row['file'];
                $path = $file->store('taxation/'.$order->id, 'local');

                TaxationDocument::query()->create([
                    'taxation_order_id' => $order->id,
                    'document_type' => $type,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'disk' => 'local',
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'status' => TaxationDocument::STATUS_PENDING,
                ]);
            }

            $order->update([
                'documents_status' => TaxationOrder::DOCUMENTS_SUBMITTED,
                'documents_note' => null,
                'status' => $order->status === TaxationOrder::STATUS_PENDING
                    ? TaxationOrder::STATUS_PROCESSING
                    : $order->status,
            ]);

            return $order->fresh(['client', 'documents']) ?? $order;
        });
    }

    public function markDocumentsVerified(TaxationOrder $order, User $admin, ?string $note = null): TaxationOrder
    {
        $this->assertReviewable($order);

        if ($order->documents()->count() === 0) {
            throw new \RuntimeException('No documents have been uploaded yet.');
        }

        $this->assertActiveDocumentsReady($order);

        $order->documents()
            ->where('status', TaxationDocument::STATUS_PENDING)
            ->update([
                'status' => TaxationDocument::STATUS_VERIFIED,
                'rejection_reason' => null,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

        $order->update([
            'documents_status' => TaxationOrder::DOCUMENTS_VERIFIED,
            'documents_note' => $note,
            'documents_reviewed_at' => now(),
            'documents_reviewed_by' => $admin->id,
            'status' => $order->status === TaxationOrder::STATUS_PENDING
                ? TaxationOrder::STATUS_PROCESSING
                : $order->status,
        ]);

        return $order->fresh(['client', 'documents', 'documentsReviewer']) ?? $order;
    }

    public function approveDocuments(TaxationOrder $order, User $admin, ?string $note = null): TaxationOrder
    {
        $this->assertReviewable($order);

        if ($order->documents()->count() === 0) {
            throw new \RuntimeException('No documents have been uploaded yet.');
        }

        $this->assertActiveDocumentsReady($order);

        $order->documents()
            ->whereIn('status', [TaxationDocument::STATUS_PENDING, TaxationDocument::STATUS_VERIFIED])
            ->update([
                'status' => TaxationDocument::STATUS_VERIFIED,
                'rejection_reason' => null,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

        $order->update([
            'documents_status' => TaxationOrder::DOCUMENTS_APPROVED,
            'documents_note' => $note,
            'documents_reviewed_at' => now(),
            'documents_reviewed_by' => $admin->id,
            'status' => TaxationOrder::STATUS_COMPLETED,
        ]);

        return $order->fresh(['client', 'documents', 'documentsReviewer']) ?? $order;
    }

    public function rejectDocuments(TaxationOrder $order, User $admin, string $reason): TaxationOrder
    {
        $this->assertReviewable($order);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'A rejection reason is required.',
            ]);
        }

        $order->documents()
            ->where('status', '!=', TaxationDocument::STATUS_REJECTED)
            ->update([
                'status' => TaxationDocument::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

        $order->update([
            'documents_status' => TaxationOrder::DOCUMENTS_REJECTED,
            'documents_note' => $reason,
            'documents_reviewed_at' => now(),
            'documents_reviewed_by' => $admin->id,
        ]);

        return $order->fresh(['client', 'documents', 'documentsReviewer']) ?? $order;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function documentPayloads(TaxationOrder $order): array
    {
        return $order->documents
            ->map(fn (TaxationDocument $document) => $this->documentPayload($document))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function documentPayload(TaxationDocument $document): array
    {
        return [
            'document_id' => $document->id,
            'type' => $document->document_type,
            'type_label' => $document->typeLabel(),
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'size' => $document->size,
            'status' => $document->status,
            'rejection_reason' => $document->rejection_reason,
            'uploaded_at' => $document->created_at?->toIso8601String(),
        ];
    }

    private function assertReviewable(TaxationOrder $order): void
    {
        if ($order->status === TaxationOrder::STATUS_CANCELLED) {
            throw new \RuntimeException('Cancelled orders cannot be reviewed.');
        }

        if ($order->documents_status === TaxationOrder::DOCUMENTS_APPROVED) {
            throw new \RuntimeException('Documents are already approved.');
        }
    }

    private function assertActiveDocumentsReady(TaxationOrder $order): void
    {
        $hasActive = $order->documents()
            ->whereIn('status', [TaxationDocument::STATUS_PENDING, TaxationDocument::STATUS_VERIFIED])
            ->exists();

        if (! $hasActive) {
            throw new \RuntimeException('Rejected documents must be replaced before verification.');
        }
    }

    public function cancelOrder(TaxationOrder $order): TaxationOrder
    {
        if ($order->status === TaxationOrder::STATUS_CANCELLED) {
            return $order;
        }

        if ($order->status === TaxationOrder::STATUS_COMPLETED) {
            throw new \RuntimeException('Completed orders cannot be cancelled.');
        }

        return DB::transaction(function () use ($order) {
            /** @var TaxationOrder $locked */
            $locked = TaxationOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->status === TaxationOrder::STATUS_CANCELLED) {
                return $locked;
            }

            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($locked->user_id);
            $wl = $this->whitelabelBillingGate->lockForUpdate($user);
            $amount = (float) $locked->amount;

            $user->creditWallet($amount, "Taxation order refund for {$locked->service_name}", $locked);
            $this->whitelabelBillingGate->refund(
                $wl,
                $amount,
                "Taxation order refund for {$locked->service_name}",
                $locked
            );

            $locked->update(['status' => TaxationOrder::STATUS_CANCELLED]);

            return $locked->fresh() ?? $locked;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOrders(User $user): array
    {
        $this->assertAccess($user);

        return TaxationOrder::query()
            ->with(['client', 'documents'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (TaxationOrder $order) => $this->orderPayload($order))
            ->all();
    }

    public function findOrder(User $user, int $orderId): TaxationOrder
    {
        $this->assertAccess($user);

        $order = TaxationOrder::query()
            ->with(['client', 'documents'])
            ->where('user_id', $user->id)
            ->find($orderId);

        if (! $order) {
            throw new \RuntimeException('Order not found.');
        }

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    public function clientPayload(TaxationClient $client): array
    {
        return [
            'client_id' => $client->id,
            'client_request_id' => $client->client_request_id,
            'first_name' => $client->first_name,
            'middle_name' => $client->middle_name,
            'last_name' => $client->last_name,
            'email' => $client->email,
            'phone' => $client->phone,
            'pan' => $client->pan,
            'aadhaar' => $client->aadhaar,
            'residence_address' => $client->residence_address,
            'residence_city' => $client->residence_city,
            'residence_pincode' => $client->residence_pincode,
            'residence_state' => $client->residence_state,
            'office_address' => $client->office_address,
            'office_city' => $client->office_city,
            'office_pincode' => $client->office_pincode,
            'office_state' => $client->office_state,
            'created_at' => $client->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function orderPayload(TaxationOrder $order): array
    {
        return [
            'order_id' => $order->id,
            'api_request_id' => $order->api_request_id,
            'client_request_id' => $order->client_request_id,
            'client_id' => $order->taxation_client_id,
            'service_id' => $order->taxation_service_id,
            'service_name' => $order->service_name,
            'amount' => (float) $order->amount,
            'status' => $order->status,
            'documents_status' => $order->documents_status,
            'documents_note' => $order->documents_note,
            'documents' => $order->relationLoaded('documents')
                ? $this->documentPayloads($order)
                : [],
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    private function nullableRequestId(mixed $value): ?string
    {
        $id = isset($value) ? trim((string) $value) : null;

        return $id === '' ? null : $id;
    }
}
