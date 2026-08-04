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
Call this **before** any Recharge or Bill Payment API. Exchange your keys for a Bearer token, then send that token on every request.

## Credentials

Copy these from **Developer panel → API Keys**:

* **Client ID** — public identifier used to request an access token
* **API Secret** — private key; never expose it in client-side code
* **Base URL** — UAT or Production host for API calls

## Prerequisites

1. Sign up on the portal
2. Complete KYC from the email link
3. Wait until an admin approves your application
4. Copy your UAT credentials from **API Keys**

## Get a Bearer token

```http
POST /api/v1/auth/client-credentials
Content-Type: application/json

{
  "client_id": "uat_client_portal_user",
  "api_secret": "uat_secret_••••••••",
  "environment": "uat",
  "device_name": "uat-live-test"
}
```

`device_name` is optional. Field name is `api_secret` (not `client_secret`).

Example success response:

```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "token_type": "Bearer",
  "environment": "uat",
  "base_url": "https://uat-api.vizodigital.com",
  "user": {
    "id": 4,
    "name": "Portal User",
    "email": "user@portal.test"
  }
}
```

Send the token on every business API call:

```http
Authorization: Bearer {token}
```

## Verify the token

```http
GET /api/v1/auth/me
Authorization: Bearer {token}
Accept: application/json
```

Example response:

```json
{
  "id": 4,
  "name": "Portal User",
  "email": "user@portal.test",
  "roles": ["developer"],
  "permissions": ["api-keys.manage"]
}
```

## Environment rules

- **UAT** credentials only work against the UAT base URL
- **Production** credentials appear only after an admin unlocks live access
- Do not mix UAT secrets with the Production host (or the reverse)
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
