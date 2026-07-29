<?php

namespace Database\Seeders;

use App\Enums\DocPageType;
use App\Enums\NavigationTargetType;
use App\Enums\PublishStatus;
use App\Models\ApiVersion;
use App\Models\DocumentationPage;
use App\Models\NavigationItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuthDocumentationSeeder extends Seeder
{
    public function run(): void
    {
        $version = ApiVersion::query()->where('slug', 'v1')->first();

        if (! $version) {
            return;
        }

        $adminId = User::query()->where('email', 'admin@portal.test')->value('id');

        $page = DocumentationPage::query()->updateOrCreate(
            [
                'api_version_id' => $version->id,
                'slug' => 'authentication',
            ],
            [
                'type' => DocPageType::Authentication,
                'title' => 'Authentication',
                'body_md' => <<<'MD'
# Authentication

This is the **first API** you must call before any business endpoint.

## Prerequisites

1. Sign up on the portal
2. Complete KYC from the email link
3. Wait until an admin approves your application
4. Copy **UAT** `client_id` and `api_secret` from **Developer panel → API Keys**

## Exchange client credentials

```http
POST /api/v1/auth/client-credentials
Content-Type: application/json

{
  "client_id": "uat_client_...",
  "api_secret": "...",
  "environment": "uat"
}
```

Successful responses return a Bearer token. Use:

```http
Authorization: Bearer {token}
```

## Environment rules

- **UAT** credentials only work against the UAT base URL. Missing or inactive UAT keys return an error.
- **Production** credentials work only after an admin unlocks live access. Pending / missing live keys return an error.
- Do not send UAT secrets to production hosts (or the reverse).

## Portal login token (optional)

Staff / approved developers can also mint a Sanctum token with email + password:

```http
POST /api/v1/auth/token
```

## Check environment access

```http
POST /api/v1/auth/credentials/check
Authorization: Bearer {token}

{ "environment": "uat" }
```
MD,
                'status' => PublishStatus::Published,
                'sidebar_key' => 'authentication',
                'show_in_sidebar' => true,
                'sort_order' => 1,
                'published_at' => now(),
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        // Publish the version shell so the auth page is reachable on fresh installs.
        if ($version->status === PublishStatus::Draft) {
            $version->forceFill([
                'status' => PublishStatus::Published,
            ])->save();
        }

        NavigationItem::query()->updateOrCreate(
            [
                'api_version_id' => $version->id,
                'label' => 'Authentication',
                'parent_id' => null,
            ],
            [
                'target_type' => NavigationTargetType::Page,
                'target_id' => $page->id,
                'route_name' => null,
                'url' => null,
                'is_visible' => true,
                'sort_order' => 1,
            ]
        );
    }
}
