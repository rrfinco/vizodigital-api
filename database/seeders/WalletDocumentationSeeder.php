<?php

namespace Database\Seeders;

use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiVersion;
use Illuminate\Database\Seeder;

class WalletDocumentationSeeder extends Seeder
{
    /**
     * Remove Wallet / Add Funds API documentation from the public docs.
     * Wallet top-up remains available in the developer panel UI, not as a public API reference.
     */
    public function run(): void
    {
        $version = ApiVersion::query()->where('slug', 'v1')->first();

        if (! $version) {
            return;
        }

        $endpoint = ApiEndpoint::query()
            ->where('api_version_id', $version->id)
            ->where('slug', 'add-funds')
            ->first();

        if ($endpoint) {
            $endpoint->requestBodies()->delete();
            $endpoint->responses()->delete();
            $endpoint->examples()->delete();
            $endpoint->codeSamples()->delete();
            $endpoint->sections()->delete();
            $endpoint->parameters()->delete();
            $endpoint->headers()->delete();
            $endpoint->errors()->delete();
            $endpoint->notes()->delete();
            $endpoint->relatedEndpoints()->detach();
            $endpoint->delete();
        }

        $category = ApiCategory::query()
            ->where('api_version_id', $version->id)
            ->where('slug', 'wallet')
            ->first();

        if ($category) {
            foreach ($category->groups as $group) {
                $group->endpoints()->each(function (ApiEndpoint $remaining): void {
                    $remaining->requestBodies()->delete();
                    $remaining->responses()->delete();
                    $remaining->examples()->delete();
                    $remaining->codeSamples()->delete();
                    $remaining->sections()->delete();
                    $remaining->parameters()->delete();
                    $remaining->headers()->delete();
                    $remaining->errors()->delete();
                    $remaining->notes()->delete();
                    $remaining->relatedEndpoints()->detach();
                    $remaining->delete();
                });
                $group->delete();
            }

            $category->delete();
        }
    }
}
