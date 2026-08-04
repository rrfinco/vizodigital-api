<?php

namespace App\Http\Middleware;

use App\Services\Whitelabel\WhitelabelContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Partner Filament panel is only reachable on a registered white-label host.
 */
class EnsurePartnerPanelHost
{
    public function __construct(
        private readonly WhitelabelContext $context,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $wl = $this->context->whitelabel();

        if (! $wl || ! $wl->isActive()) {
            abort(404, 'This partner portal is not available on this domain. Use your white-label domain.');
        }

        return $next($request);
    }
}
