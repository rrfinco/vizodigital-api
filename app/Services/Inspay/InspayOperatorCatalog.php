<?php

namespace App\Services\Inspay;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class InspayOperatorCatalog
{
    private const CACHE_KEY = 'inspay.operator_catalog.v1';

    /**
     * @return Collection<int, array{category: string, name: string, code: string}>
     */
    public function all(): Collection
    {
        /** @var list<array{category: string, name: string, code: string}> $rows */
        $rows = Cache::rememberForever(self::CACHE_KEY, function (): array {
            $path = database_path('data/inspay_operators.json');

            if (! is_file($path)) {
                return [];
            }

            $decoded = json_decode((string) file_get_contents($path), true);

            if (! is_array($decoded)) {
                return [];
            }

            return array_values(array_filter($decoded, function ($row): bool {
                return is_array($row)
                    && isset($row['category'], $row['name'], $row['code'])
                    && is_string($row['category'])
                    && is_string($row['name'])
                    && is_string($row['code']);
            }));
        });

        return collect($rows);
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return $this->all()
            ->pluck('category')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{category: string, name: string, code: string}>
     */
    public function search(?string $category = null, ?string $query = null): Collection
    {
        $operators = $this->all();

        $category = is_string($category) ? trim($category) : '';
        $query = is_string($query) ? trim($query) : '';

        if ($category !== '') {
            $operators = $operators->where('category', $category)->values();
        }

        if ($query !== '') {
            $needle = mb_strtolower($query);
            $operators = $operators
                ->filter(function (array $row) use ($needle): bool {
                    return str_contains(mb_strtolower($row['name']), $needle)
                        || str_contains(mb_strtolower($row['code']), $needle)
                        || str_contains(mb_strtolower($row['category']), $needle);
                })
                ->values();
        }

        return $operators;
    }
}
