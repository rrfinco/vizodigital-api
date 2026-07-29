<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Traits\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    use HasPublishStatus;

    protected $fillable = [
        'api_version_id',
        'question',
        'answer_md',
        'category',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApiVersion::class, 'api_version_id');
    }
}
