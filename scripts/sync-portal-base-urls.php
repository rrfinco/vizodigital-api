<?php

/**
 * Sync ApiEnvironment.base_url from portal config / env.
 *
 * Local Docker:
 *   docker exec -it api-portal-app php scripts/sync-portal-base-urls.php
 *
 * Live (Dokploy shell / host .env pointed at live DB):
 *   php scripts/sync-portal-base-urls.php
 */

declare(strict_types=1);

use App\Enums\EnvironmentSlug;
use App\Models\ApiEnvironment;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$map = [
    EnvironmentSlug::Uat->value => (string) config('portal.environments.uat.base_url'),
    EnvironmentSlug::Production->value => (string) config('portal.environments.production.base_url'),
];

$host = (string) config('database.connections.'.config('database.default').'.host');
$database = (string) config('database.connections.'.config('database.default').'.database');

echo "DB {$host}/{$database}".PHP_EOL;

foreach ($map as $slug => $baseUrl) {
    if ($baseUrl === '' || str_contains($baseUrl, 'example.com')) {
        fwrite(STDERR, "Skip {$slug}: set PORTAL_*_BASE_URL to a real domain first ({$baseUrl}).".PHP_EOL);
        continue;
    }

    $env = ApiEnvironment::query()->where('slug', $slug)->first();
    if (! $env) {
        fwrite(STDERR, "Missing environment row: {$slug}".PHP_EOL);
        continue;
    }

    $before = $env->base_url;
    $env->forceFill(['base_url' => rtrim($baseUrl, '/')])->save();
    echo "{$slug}: {$before} -> {$env->fresh()->base_url}".PHP_EOL;
}

echo 'Done.'.PHP_EOL;
