<?php

namespace App\Filament\User\Pages;

use App\Enums\EnvironmentSlug;
use App\Models\ApiCredential;
use App\Models\ApiEnvironment;
use App\Services\Whitelabel\WhitelabelEnvironmentUrls;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class Credentials extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'API Keys';

    protected static ?string $title = 'API Keys';

    protected string $view = 'filament.user.pages.credentials';

    /**
     * @return Collection<int, array{environment: ApiEnvironment, credential: ?ApiCredential, base_url: string}>
     */
    public function getCredentialPanels(): Collection
    {
        $user = auth()->user();
        $urls = app(WhitelabelEnvironmentUrls::class);

        $credentials = $user
            ? $user->apiCredentials()->with('environment')->get()->keyBy('api_environment_id')
            : collect();

        return ApiEnvironment::query()
            ->where('is_enabled', true)
            ->whereIn('slug', [EnvironmentSlug::Uat, EnvironmentSlug::Production])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ApiEnvironment $environment): array => [
                'environment' => $environment,
                'credential' => $credentials->get($environment->id),
                'base_url' => $urls->resolve($environment, user: $user),
            ]);
    }
}
