<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

class WhitelabelUnavailableException extends \RuntimeException
{
    public const REASON_SUSPENDED = 'suspended';

    public const REASON_FLOAT_EXHAUSTED = 'float_exhausted';

    public function __construct(
        public readonly string $reason,
        public readonly ?int $whitelabelId = null,
    ) {
        parent::__construct('Service temporarily unavailable. Please try again later.');
    }

    public function toJsonResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'code' => 'SERVICE_UNAVAILABLE',
            'message' => $this->getMessage(),
        ], 503);
    }
}
