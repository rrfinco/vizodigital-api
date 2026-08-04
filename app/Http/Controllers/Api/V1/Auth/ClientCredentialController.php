<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\EnvironmentSlug;
use App\Http\Controllers\Controller;
use App\Services\Credentials\CredentialProvisioner;
use App\Services\Whitelabel\WhitelabelEnvironmentUrls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClientCredentialController extends Controller
{
    public function __construct(
        private readonly CredentialProvisioner $credentials,
        private readonly WhitelabelEnvironmentUrls $whitelabelUrls,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string', 'max:255'],
            'api_secret' => ['required', 'string', 'max:255'],
            'environment' => ['required', Rule::enum(EnvironmentSlug::class)],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $slug = EnvironmentSlug::from($validated['environment']);

        try {
            $credential = $this->credentials->authenticateClient(
                $validated['client_id'],
                $validated['api_secret'],
                $slug,
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first() ?: 'Invalid credentials.',
                'errors' => $exception->errors(),
            ], 401);
        }

        $user = $credential->user;
        $token = $user->createToken(
            $validated['device_name'] ?? strtoupper($slug->value).'-api',
            ['*']
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'environment' => $slug->value,
            'base_url' => $credential->environment
                ? $this->whitelabelUrls->resolve($credential->environment, user: $user)
                : null,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'environment' => ['required', Rule::enum(EnvironmentSlug::class)],
        ]);

        $user = $request->user();
        $slug = EnvironmentSlug::from($validated['environment']);

        try {
            $credential = $this->credentials->assertUsable($user, $slug);
        } catch (ValidationException $exception) {
            return response()->json([
                'ok' => false,
                'environment' => $slug->value,
                'message' => collect($exception->errors())->flatten()->first(),
                'errors' => $exception->errors(),
            ], 403);
        }

        return response()->json([
            'ok' => true,
            'environment' => $slug->value,
            'base_url' => $credential->environment
                ? $this->whitelabelUrls->resolve($credential->environment, user: $user)
                : null,
            'client_id' => $credential->client_id,
            'status' => $credential->status->value,
        ]);
    }
}
