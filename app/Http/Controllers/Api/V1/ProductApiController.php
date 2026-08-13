<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\WhitelabelUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProductApi\ProductApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

class ProductApiController extends Controller
{
    public function __construct(
        private readonly ProductApiService $productApi
    ) {}

    public function categories(Request $request): JsonResponse
    {
        return $this->respond($request, function (User $user) {
            $result = $this->productApi->productCategories($user);
            $provider = $result['provider'];

            return [
                'message' => $provider['message'] ?? 'Product category fetched',
                'data' => $this->normalizeCategories($provider['data'] ?? []),
                'fee' => $result['fee'],
                'wallet_balance' => $result['wallet_balance'],
            ];
        });
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $categoryId = (int) $validator->validated()['category_id'];

        return $this->respond($request, function (User $user) use ($categoryId) {
            $result = $this->productApi->productsByCategory($user, $categoryId);
            $provider = $result['provider'];

            return [
                'message' => $provider['message'] ?? 'Products fetched',
                'data' => $this->normalizeProducts($provider['data'] ?? []),
                'fee' => $result['fee'],
                'wallet_balance' => $result['wallet_balance'],
            ];
        });
    }

    public function details(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'string', 'max:64'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'card_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $validated = $validator->validated();

        return $this->respond($request, function (User $user) use ($validated) {
            $result = $this->productApi->productDetails($user, $validated);
            $provider = $result['provider'];

            return [
                'message' => $provider['message'] ?? 'Product details fetched',
                'data' => $this->normalizeDetails($provider['data'] ?? []),
                'fee' => $result['fee'],
                'wallet_balance' => $result['wallet_balance'],
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

    /**
     * @param  mixed  $data
     * @return list<array{id: int, title: string}>
     */
    private function normalizeCategories(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $categories = [];

        foreach ($data as $row) {
            if (! is_array($row)) {
                continue;
            }

            $categories[] = [
                'id' => (int) ($row['id'] ?? 0),
                'title' => (string) ($row['title'] ?? ''),
            ];
        }

        return $categories;
    }

    /**
     * @param  mixed  $data
     * @return list<array{product_id: string, title: string, sub_title: string, logo: string}>
     */
    private function normalizeProducts(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $products = [];

        foreach ($data as $row) {
            if (! is_array($row)) {
                continue;
            }

            $products[] = [
                'product_id' => (string) ($row['product_id'] ?? ''),
                'title' => (string) ($row['title'] ?? ''),
                'sub_title' => (string) ($row['sub_title'] ?? ''),
                'logo' => (string) ($row['logo'] ?? ''),
            ];
        }

        return $products;
    }

    /**
     * @param  mixed  $data
     * @return array{url: string, campaign_url: string}
     */
    private function normalizeDetails(mixed $data): array
    {
        $row = is_array($data) ? $data : [];
        $url = (string) ($row['url'] ?? $row['campaign_url'] ?? '');
        $campaignUrl = (string) ($row['campaign_url'] ?? $row['url'] ?? '');

        return [
            'url' => $url,
            'campaign_url' => $campaignUrl,
        ];
    }
}
