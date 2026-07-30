<?php

namespace App\Providers;

use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiGroup;
use App\Models\ChangelogEntry;
use App\Models\DocumentationPage;
use App\Models\Faq;
use App\Models\User;
use App\Observers\SearchableObserver;
use App\Policies\DocumentationPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Portal\PortalContext::class);
        $this->app->singleton(\App\Services\Portal\PortalSettings::class);
    }

    public function boot(): void
    {
        // Relative Vite URLs avoid http/https mixed-content behind reverse proxies.
        Vite::createAssetPathsUsing(fn (string $path, ?bool $secure = null): string => '/'.ltrim($path, '/'));

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::policy(User::class, UserPolicy::class);

        Gate::define('docs.view_admin', [DocumentationPolicy::class, 'viewAdmin']);
        Gate::define('docs.create', [DocumentationPolicy::class, 'create']);
        Gate::define('docs.update', [DocumentationPolicy::class, 'update']);
        Gate::define('docs.publish', [DocumentationPolicy::class, 'publish']);
        Gate::define('docs.delete', [DocumentationPolicy::class, 'delete']);
        Gate::define('docs.preview', [DocumentationPolicy::class, 'preview']);

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasRole('super_admin')) {
                return true;
            }

            return null;
        });

        $this->registerSearchObservers();
        $this->registerDocsViewComposer();
    }

    private function registerSearchObservers(): void
    {
        foreach ([
            ApiEndpoint::class,
            DocumentationPage::class,
            ApiCategory::class,
            ApiGroup::class,
            Faq::class,
            ChangelogEntry::class,
        ] as $model) {
            $model::observe(SearchableObserver::class);
        }
    }

    private function registerDocsViewComposer(): void
    {
        View::composer('layouts.docs', function ($view): void {
            if ($view->offsetExists('portalContext')) {
                return;
            }

            /** @var \App\Services\Portal\PortalContext $portal */
            $portal = app(\App\Services\Portal\PortalContext::class);
            $portal->resolve(request());

            $envSlug = $portal->environment()?->slug instanceof \BackedEnum
                ? $portal->environment()->slug->value
                : $portal->environment()?->slug;

            $view->with([
                'portalContext' => $portal,
                'portalVersion' => $portal->version(),
                'portalEnvironment' => $portal->environment(),
                'portalVersions' => $portal->versions(),
                'portalEnvironments' => $portal->environments(),
                'portalVersionUrls' => $portal->versions()->mapWithKeys(
                    fn ($version) => [$version->slug => route('docs.explorer', array_filter(['version' => $version->slug, 'env' => $envSlug]))]
                ),
                'portalEnvironmentUrls' => $portal->environments()->mapWithKeys(function ($environment) use ($envSlug): array {
                    $slug = $environment->slug instanceof \BackedEnum
                        ? $environment->slug->value
                        : (string) $environment->slug;

                    return [$slug => request()->fullUrlWithQuery(['env' => $slug])];
                }),
                'portalNav' => app(\App\Services\Portal\SidebarBuilder::class)->build($portal->version()),
            ]);
        });
    }
}
