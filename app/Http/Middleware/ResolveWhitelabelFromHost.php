<?php

namespace App\Http\Middleware;

use App\Services\Whitelabel\WhitelabelContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveWhitelabelFromHost
{
    public function __construct(
        private readonly WhitelabelContext $context,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->context->resolveFromHost($request->getHost());

        return $next($request);
    }
}
