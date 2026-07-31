<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectPortalWebWhenApiOnly
{
    /**
     * Paths still allowed when PORTAL_API_ONLY=true (business API uses the api route group).
     *
     * @var list<string>
     */
    private array $allowedExact = [
        'up',
        'payment/webhook',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('portal.api_only')) {
            return $next($request);
        }

        // Business API must keep working on the UAT host.
        if ($request->is('api', 'api/*')) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        if (in_array($path, $this->allowedExact, true)) {
            return $next($request);
        }

        foreach ($this->allowedExact as $allowed) {
            if ($path !== '' && str_starts_with($path, $allowed.'/')) {
                return $next($request);
            }
        }

        if ($path === '') {
            return response()->json([
                'service' => config('app.name'),
                'mode' => 'api_only',
                'message' => 'This host is API-only (UAT). Use the developer portal for docs and onboarding.',
                'portal' => config('portal.public_url'),
                'api_base' => rtrim((string) config('app.url'), '/').'/api/v1',
            ]);
        }

        return response()->json([
            'message' => 'Web portal, docs, and onboarding are disabled on this host. Use the developer portal instead.',
            'portal' => config('portal.public_url'),
            'api_base' => rtrim((string) config('app.url'), '/').'/api/v1',
        ], 404);
    }
}
