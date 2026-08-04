<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @param  list<string>  $tables
 * @param  list<string>  $planSlugs
 * @return array<string, mixed>
 */
function compare_db_snapshot(array $tables, array $planSlugs): array
{
    $migrations = [];
    if (Schema::hasTable('migrations')) {
        $migrations = DB::table('migrations')->orderBy('migration')->pluck('migration')->all();
    }

    $counts = [];
    foreach ($tables as $table) {
        $counts[$table] = Schema::hasTable($table) ? DB::table($table)->count() : null;
    }

    $planEndpoints = [];
    if (Schema::hasTable('api_endpoints')) {
        $cols = ['slug', 'status'];
        if (Schema::hasColumn('api_endpoints', 'access_service_key')) {
            $cols[] = 'access_service_key';
        }
        $planEndpoints = DB::table('api_endpoints')
            ->whereIn('slug', $planSlugs)
            ->get($cols)
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    $settingKeys = Schema::hasTable('settings')
        ? DB::table('settings')->orderBy('key')->pluck('key')->all()
        : [];

    $settingGroups = [];
    if (Schema::hasTable('settings')) {
        $settingGroups = DB::table('settings')
            ->selectRaw('`group`, count(*) as c')
            ->groupBy('group')
            ->pluck('c', 'group')
            ->all();
    }

    $roles = Schema::hasTable('roles')
        ? DB::table('roles')->orderBy('name')->pluck('name')->all()
        : [];

    $envs = [];
    if (Schema::hasTable('api_environments')) {
        $envCols = ['slug', 'name', 'base_url'];
        if (Schema::hasColumn('api_environments', 'is_enabled')) {
            $envCols[] = 'is_enabled';
        }
        $envs = DB::table('api_environments')->orderBy('slug')->get($envCols)->map(fn ($r) => (array) $r)->all();
    }

    $versions = [];
    if (Schema::hasTable('api_versions')) {
        $versions = DB::table('api_versions')
            ->orderBy('slug')
            ->get(['slug', 'name', 'status', 'is_default'])
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    $publishedEndpoints = Schema::hasTable('api_endpoints')
        ? DB::table('api_endpoints')->where('status', 'published')->count()
        : 0;

    $gated = Schema::hasTable('api_endpoints') && Schema::hasColumn('api_endpoints', 'access_service_key')
        ? DB::table('api_endpoints')->whereNotNull('access_service_key')->count()
        : 0;

    $pendingMigrations = [];
    try {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles(database_path('migrations'));
        $ran = array_flip($migrations);
        foreach (array_keys($files) as $name) {
            if (! isset($ran[$name])) {
                $pendingMigrations[] = $name;
            }
        }
    } catch (Throwable) {
        // ignore
    }

    return compact(
        'migrations',
        'counts',
        'planEndpoints',
        'settingKeys',
        'settingGroups',
        'roles',
        'envs',
        'versions',
        'publishedEndpoints',
        'gated',
        'pendingMigrations',
    );
}
