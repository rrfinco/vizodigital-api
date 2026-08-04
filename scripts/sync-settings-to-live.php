<?php

/**
 * Copy admin Portal Settings from local Docker DB → live DB (.env).
 *
 * Groups: recharge, payment, branding (Roundpay / RRFinco / Inspay / EkycHub / bank / brand).
 * Prints only key names — never secret values.
 *
 * Usage:
 *   php scripts/sync-settings-to-live.php --dry-run
 *   php scripts/sync-settings-to-live.php
 */

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$dryRun = in_array('--dry-run', $argv ?? [], true);
$groups = ['recharge', 'payment', 'branding'];

$targetHost = (string) config('database.connections.'.config('database.default').'.host');
$targetDb = (string) config('database.connections.'.config('database.default').'.database');
$targetPort = (string) config('database.connections.'.config('database.default').'.port');

echo '[sync-settings] dry_run='.($dryRun ? 'yes' : 'no').PHP_EOL;
echo "[sync-settings] target={$targetHost}:{$targetPort}/{$targetDb}".PHP_EOL;

$exportPhp = <<<'PHP'
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$groups = ["recharge", "payment", "branding"];
$rows = App\Models\Setting::query()
    ->whereIn("group", $groups)
    ->orderBy("group")
    ->orderBy("key")
    ->get(["group", "key", "value"])
    ->map(fn ($r) => ["group" => $r->group, "key" => $r->key, "value" => $r->value])
    ->all();
echo json_encode([
    "host" => config("database.connections.".config("database.default").".host"),
    "database" => config("database.connections.".config("database.default").".database"),
    "rows" => $rows,
], JSON_THROW_ON_ERROR);
PHP;

$cmd = 'docker exec api-portal-app php -r '.escapeshellarg($exportPhp);
$json = shell_exec($cmd);
if (! is_string($json) || trim($json) === '') {
    fwrite(STDERR, "[sync-settings] Failed to export settings from Docker (is api-portal-app running?).\n");
    exit(1);
}

try {
    /** @var array{host?: string, database?: string, rows: list<array{group: string, key: string, value: mixed}>} $payload */
    $payload = json_decode(trim($json), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    fwrite(STDERR, '[sync-settings] Invalid export JSON: '.$e->getMessage().PHP_EOL);
    exit(1);
}

$sourceRows = $payload['rows'] ?? [];
echo '[sync-settings] source='.($payload['host'] ?? '?').'/'.($payload['database'] ?? '?').' rows='.count($sourceRows).PHP_EOL;

if ($sourceRows === []) {
    fwrite(STDERR, '[sync-settings] No settings in source for groups: '.implode(', ', $groups).PHP_EOL);
    exit(1);
}

if (($payload['host'] ?? null) === $targetHost && ($payload['database'] ?? null) === $targetDb) {
    echo '[sync-settings] Source and target appear to be the same DB — nothing to merge.'.PHP_EOL;
    echo '[sync-settings] Keys present:'.PHP_EOL;
    foreach ($sourceRows as $row) {
        echo '  · '.$row['group'].'/'.$row['key'].PHP_EOL;
    }
    exit(0);
}

$upserted = 0;
$unchanged = 0;

foreach ($sourceRows as $row) {
    $key = (string) $row['key'];
    $group = (string) $row['group'];
    $value = $row['value'];

    $existing = DB::table('settings')->where('key', $key)->first();
    $existingValue = null;
    if ($existing && $existing->value !== null) {
        $existingValue = is_string($existing->value)
            ? json_decode($existing->value, true)
            : $existing->value;
    }

    $same = $existing
        && (string) $existing->group === $group
        && json_encode($existingValue) === json_encode($value);

    if ($same) {
        $unchanged++;
        echo "  = {$group}/{$key}".PHP_EOL;
        continue;
    }

    echo ($existing ? '  ~ ' : '  + ')."{$group}/{$key}".PHP_EOL;

    if (! $dryRun) {
        $encoded = json_encode($value);
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $encoded,
                'updated_at' => now(),
                'created_at' => $existing->created_at ?? now(),
            ]
        );
    }

    $upserted++;
}

if (! $dryRun) {
    try {
        cache()->forget(\App\Services\Portal\PortalSettings::CACHE_KEY);
    } catch (Throwable) {
        // ignore
    }
}

echo "[sync-settings] upserted={$upserted} unchanged={$unchanged}".PHP_EOL;
echo '[sync-settings] Done.'.($dryRun ? ' (dry-run, nothing written)' : '').PHP_EOL;
