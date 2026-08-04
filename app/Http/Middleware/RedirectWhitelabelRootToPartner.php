<?php

namespace App\Http\Middleware;

use App\Services\Whitelabel\WhitelabelContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * On a white-label host, send visitors to the partner portal home.
 */
class RedirectWhitelabelRootToPartner
{
    public function __construct(
        private readonly WhitelabelContext $context,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->context->isActive()) {
            return $next($request);
        }

        // Only redirect bare landing "/" — leave /register, /docs, /user, etc.
        if ($request->is('/')) {
            return redirect('/partner');
        }

        return $next($request);
    }
}
