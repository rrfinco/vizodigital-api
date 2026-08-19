<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\WhitelabelUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\TaxationDocument;
use App\Models\User;
use App\Services\Taxation\TaxationApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

class TaxationController extends Controller
{
    public function __construct(
        private readonly TaxationApiService $taxation,
    ) {}

    public function storeClient(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'digits:10'],
            'pan' => ['required', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/i'],
            'aadhaar' => ['required', 'digits:12'],
            'residence_address' => ['required', 'string', 'max:500'],
            'residence_city' => ['required', 'string', 'max:100'],
            'residence_pincode' => ['required', 'digits:6'],
            'residence_state' => ['required', 'string', 'max:100'],
            'office_address' => ['required', 'string', 'max:500'],
            'office_city' => ['required', 'string', 'max:100'],
            'office_pincode' => ['required', 'digits:6'],
            'office_state' => ['required', 'string', 'max:100'],
            'client_request_id' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->respond($request, function (User $user) use ($validator) {
            $client = $this->taxation->createClient($user, $validator->validated());

            return [
                'message' => 'Client created successfully.',
                'data' => $this->taxation->clientPayload($client),
                'fee' => 0.0,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        });
    }

    public function clients(Request $request): JsonResponse
    {
        return $this->respond($request, function (User $user) {
            return [
                'message' => 'Clients fetched.',
                'data' => $this->taxation->listClients($user),
                'fee' => 0.0,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        });
    }

    public function services(Request $request): JsonResponse
    {
        $category = trim((string) $request->query('category', ''));

        return $this->respond($request, function (User $user) use ($category) {
            return [
                'message' => 'Services fetched.',
                'data' => $this->taxation->listServices($user, $category === '' ? null : $category),
                'fee' => 0.0,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        });
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_id' => ['required', 'integer', 'min:1'],
            'service_id' => ['required', 'integer', 'min:1'],
            'client_request_id' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->respond($request, function (User $user) use ($validator) {
            $order = $this->taxation->createOrder($user, $validator->validated());

            return [
                'message' => 'Order created successfully.',
                'data' => $this->taxation->orderPayload($order),
                'fee' => (float) $order->amount,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        });
    }

    public function orders(Request $request): JsonResponse
    {
        return $this->respond($request, function (User $user) {
            return [
                'message' => 'Orders fetched.',
                'data' => $this->taxation->listOrders($user),
                'fee' => 0.0,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        });
    }

    public function showOrder(Request $request, int $order): JsonResponse
    {
        return $this->respond($request, function (User $user) use ($order) {
            $record = $this->taxation->findOrder($user, $order);

            return [
                'message' => 'Order fetched.',
                'data' => $this->taxation->orderPayload($record),
                'fee' => 0.0,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        });
    }

    public function storeDocuments(Request $request, int $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'documents' => ['required', 'array', 'min:1', 'max:10'],
            'documents.*.type' => ['required', 'string', 'in:'.implode(',', array_keys(TaxationDocument::TYPES))],
            'documents.*.file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return $this->respond($request, function (User $user) use ($validator, $order) {
            $files = [];
            foreach ($validator->validated()['documents'] as $row) {
                $files[] = [
                    'type' => $row['type'],
                    'file' => $row['file'],
                ];
            }

            $record = $this->taxation->uploadDocuments($user, $order, $files);

            return [
                'message' => 'Documents uploaded. Admin will verify and approve them.',
                'data' => $this->taxation->orderPayload($record),
                'fee' => 0.0,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        });
    }

    public function documents(Request $request, int $order): JsonResponse
    {
        return $this->respond($request, function (User $user) use ($order) {
            $record = $this->taxation->findOrder($user, $order);

            return [
                'message' => 'Documents fetched.',
                'data' => [
                    'order_id' => $record->id,
                    'documents_status' => $record->documents_status,
                    'documents_note' => $record->documents_note,
                    'documents' => $this->taxation->documentPayloads($record),
                ],
                'fee' => 0.0,
                'wallet_balance' => (float) $user->fresh()->wallet_balance,
            ];
        });
    }

    /**
     * @param  callable(User): array{message: string, data: mixed, fee: float, wallet_balance: float}  $callback
     */
    private function respond(Request $request, callable $callback): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isOnboardingApproved()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your onboarding status is not approved yet.',
            ], 403);
        }

        try {
            $payload = $callback($user);

            return response()->json([
                'status' => 'success',
                'message' => $payload['message'],
                'data' => $payload['data'],
                'fee' => $payload['fee'],
                'wallet_balance' => $payload['wallet_balance'],
            ]);
        } catch (WhitelabelUnavailableException $e) {
            return $e->toJsonResponse();
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => collect($e->errors())->flatten()->first() ?: 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\RuntimeException $e) {
            $status = str_contains($e->getMessage(), 'not enabled') ? 403 : 400;

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $status);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    private function validationError(MessageBag $errors): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation error',
            'errors' => $errors,
        ], 422);
    }
}
