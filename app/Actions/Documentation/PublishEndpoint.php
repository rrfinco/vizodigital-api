<?php

namespace App\Actions\Documentation;

use App\Enums\PublishStatus;
use App\Models\ApiEndpoint;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class PublishEndpoint
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function __invoke(ApiEndpoint $endpoint, ?User $actor = null): ApiEndpoint
    {
        $actor ??= auth()->user();

        if (! $actor?->can('docs.publish')) {
            throw new AuthorizationException('You are not allowed to publish documentation.');
        }

        if ($endpoint->status === PublishStatus::Published) {
            return $endpoint;
        }

        return DB::transaction(function () use ($endpoint, $actor): ApiEndpoint {
            $old = [
                'status' => $endpoint->status?->value,
                'published_at' => $endpoint->published_at?->toIso8601String(),
            ];

            $endpoint->forceFill([
                'status' => PublishStatus::Published,
                'published_at' => $endpoint->published_at ?? now(),
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->log(
                action: 'endpoint.published',
                auditable: $endpoint,
                oldValues: $old,
                newValues: [
                    'status' => PublishStatus::Published->value,
                    'published_at' => $endpoint->published_at?->toIso8601String(),
                ],
                user: $actor,
            );

            return $endpoint->refresh();
        });
    }
}
