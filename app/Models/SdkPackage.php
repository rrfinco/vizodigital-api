<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Enums\SnippetLanguage;
use App\Traits\HasPublishStatus;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdkPackage extends Model
{
    use HasPublishStatus;
    use HasSlug;

    protected $fillable = [
        'api_version_id',
        'name',
        'slug',
        'language',
        'status',
        'install_md',
        'repo_url',
        'package_name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'language' => SnippetLanguage::class,
            'status' => PublishStatus::class,
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApiVersion::class, 'api_version_id');
    }
}
