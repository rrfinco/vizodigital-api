<?php

namespace Database\Seeders;

use App\Models\TaxationCategory;
use App\Models\TaxationService;
use App\Services\Taxation\TaxationCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxationCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [];

        foreach (TaxationCatalog::categories() as $category) {
            $row = TaxationCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ],
            );
            $categories[$category['name']] = $row->id;
        }

        $sort = 1;
        foreach (TaxationCatalog::services() as $service) {
            $categoryId = $categories[$service['category']] ?? null;
            if ($categoryId === null) {
                $slug = Str::slug($service['category']);
                $row = TaxationCategory::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $service['category'],
                        'sort_order' => 99,
                        'is_active' => true,
                    ],
                );
                $categories[$service['category']] = $row->id;
                $categoryId = $row->id;
            }

            TaxationService::query()->updateOrCreate(
                ['id' => $service['id']],
                [
                    'taxation_category_id' => $categoryId,
                    'name' => $service['name'],
                    'price' => $service['price'],
                    'default_commission_percentage' => TaxationCatalog::DEFAULT_COMMISSION,
                    'is_active' => true,
                    'sort_order' => $sort++,
                ],
            );
        }
    }
}
