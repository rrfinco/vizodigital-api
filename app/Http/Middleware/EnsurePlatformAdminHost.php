<?php

namespace App\Http\Middleware;

use App\Services\Whitelabel\WhitelabelContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform admin panel must not be served on white-label partner domains.
 */
class EnsurePlatformAdminHost
{
    public function __construct(
        private readonly WhitelabelContext $context,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->context->whitelabel()) {
            abort(404, 'Admin panel is not available on this domain.');
        }

        return $next($request);
    }
}
