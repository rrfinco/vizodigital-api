<?php

/**
 * Compare local Docker DB seed/state vs host .env (live) DB.
 * Prints counts and keys only — no secret setting values.
 *
 * Usage:
 *   php scripts/compare-db-seed-state.php
 */

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/_compare_db_snap_fn.php';

$tables = [
    'users',
    'roles',
    'permissions',
    'settings',
    'api_versions',
    'api_environments',
    'api_categories',
    'api_groups',
    'api_endpoints',
    'user_plan_api_access',
    'whitelabels',
    'whitelabel_plan_api_access',
    'documentation_pages',
    'faqs',
    'navigation_items',
    'search_documents',
];

$planSlugs = ['operator-fetch', 'operator-plan-fetch', 'dth-plan-fetch', 'dth-info'];

$exportPhp = <<<'PHP'
require "vendor/autoload.php";
require "scripts/_compare_db_snap_fn.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$tables = json_decode(getenv("COMPARE_TABLES") ?: "[]", true) ?: [];
$planSlugs = json_decode(getenv("COMPARE_PLAN_SLUGS") ?: "[]", true) ?: [];
echo json_encode([
  "host" => config("database.connections.".config("database.default").".host"),
  "database" => config("database.connections.".config("database.default").".database"),
  "snap" => compare_db_snapshot($tables, $planSlugs),
], JSON_THROW_ON_ERROR);
PHP;

$cmd = 'docker exec'
    .' -e COMPARE_TABLES='.escapeshellarg(json_encode($tables))
    .' -e COMPARE_PLAN_SLUGS='.escapeshellarg(json_encode($planSlugs))
    .' api-portal-app php -r '.escapeshellarg($exportPhp);

$dockerJson = shell_exec($cmd.' 2>/dev/null');
if (! is_string($dockerJson) || trim($dockerJson) === '') {
    fwrite(STDERR, "Failed to snapshot Docker DB.\n");
    exit(1);
}

try {
    $docker = json_decode(trim($dockerJson), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    fwrite(STDERR, "Invalid Docker snapshot JSON: {$e->getMessage()}\n");
    exit(1);
}

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$live = [
    'host' => (string) config('database.connections.'.config('database.default').'.host'),
    'database' => (string) config('database.connections.'.config('database.default').'.database'),
    'snap' => compare_db_snapshot($tables, $planSlugs),
];

$d = $docker['snap'];
$l = $live['snap'];

echo "=== DB compare (Docker local vs Live) ===\n";
echo 'Docker: '.$docker['host'].'/'.$docker['database']."\n";
echo 'Live:   '.$live['host'].'/'.$live['database']."\n\n";

$dm = $d['migrations'];
$lm = $l['migrations'];
$onlyD = array_values(array_diff($dm, $lm));
$onlyL = array_values(array_diff($lm, $dm));
echo 'Migrations ran: docker='.count($dm).' live='.count($lm)."\n";
echo '  only_on_docker: '.(count($onlyD) ? implode(', ', $onlyD) : '(none)')."\n";
echo '  only_on_live: '.(count($onlyL) ? implode(', ', $onlyL) : '(none)')."\n";
echo '  pending_files docker: '.(count($d['pendingMigrations'] ?? []) ? implode(', ', $d['pendingMigrations']) : '(none)')."\n";
echo '  pending_files live: '.(count($l['pendingMigrations'] ?? []) ? implode(', ', $l['pendingMigrations']) : '(none)')."\n\n";

echo "Table counts:\n";
printf("  %-30s %8s %8s %s\n", 'table', 'docker', 'live', 'note');
foreach ($tables as $t) {
    $c = $d['counts'][$t] ?? null;
    $lc = $l['counts'][$t] ?? null;
    $note = ($c === $lc) ? 'OK' : 'DIFF';
    printf("  %-30s %8s %8s %s\n", $t, $c === null ? 'MISS' : (string) $c, $lc === null ? 'MISS' : (string) $lc, $note);
}

echo "\nPublished endpoints: docker={$d['publishedEndpoints']} live={$l['publishedEndpoints']}\n";
echo "Gated endpoints: docker={$d['gated']} live={$l['gated']}\n\n";

echo "Plan endpoints:\n";
$dp = collect($d['planEndpoints'])->keyBy('slug');
$lp = collect($l['planEndpoints'])->keyBy('slug');
foreach ($planSlugs as $slug) {
    $a = $dp->get($slug);
    $b = $lp->get($slug);
    $fmt = static function (?array $row): string {
        if (! $row) {
            return 'MISSING';
        }
        $key = $row['access_service_key'] ?? 'n/a';

        return "status={$row['status']} key={$key}";
    };
    echo "  {$slug}\n";
    echo '    docker: '.$fmt($a)."\n";
    echo '    live:   '.$fmt($b)."\n";
}

echo "\nSettings keys: docker=".count($d['settingKeys']).' live='.count($l['settingKeys'])."\n";
$skOnlyD = array_values(array_diff($d['settingKeys'], $l['settingKeys']));
$skOnlyL = array_values(array_diff($l['settingKeys'], $d['settingKeys']));
echo '  only_on_docker: '.(count($skOnlyD) ? implode(', ', $skOnlyD) : '(none)')."\n";
echo '  only_on_live: '.(count($skOnlyL) ? implode(', ', $skOnlyL) : '(none)')."\n";
echo '  groups docker: '.json_encode($d['settingGroups'])."\n";
echo '  groups live:   '.json_encode($l['settingGroups'])."\n\n";

echo 'Roles docker: '.implode(', ', $d['roles'])."\n";
echo 'Roles live:   '.implode(', ', $l['roles'])."\n\n";

echo "Environments:\n";
foreach ([['docker', $d['envs']], ['live', $l['envs']]] as [$lab, $envs]) {
    echo "  {$lab}:\n";
    foreach ($envs as $e) {
        $en = isset($e['is_enabled']) ? ' enabled='.($e['is_enabled'] ? '1' : '0') : '';
        echo "    {$e['slug']}{$en} base_url={$e['base_url']}\n";
    }
}

echo "\nVersions:\n";
foreach ([['docker', $d['versions']], ['live', $l['versions']]] as [$lab, $vs]) {
    echo "  {$lab}:\n";
    foreach ($vs as $v) {
        echo "    {$v['slug']} status={$v['status']} default=".(! empty($v['is_default']) ? 'yes' : 'no')."\n";
    }
}

echo "\nDone.\n";
