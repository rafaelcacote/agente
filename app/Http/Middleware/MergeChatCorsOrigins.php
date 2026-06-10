<?php

namespace App\Http\Middleware;

use App\Services\Cors\AllowedOriginRegistry;
use App\Support\AllowedOrigin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mescla origens dos tenants na config CORS antes do HandleCors do Laravel.
 * Necessário para que preflight OPTIONS funcione em domínios cadastrados por tenant.
 */
class MergeChatCorsOrigins
{
    public function __construct(private AllowedOriginRegistry $registry)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/chat', 'api/chat/*')) {
            $origins = AllowedOrigin::normalizeList(array_merge(
                config('cors.allowed_origins', []),
                $this->registry->allTenantOrigins()
            ));

            config(['cors.allowed_origins' => $origins]);
        }

        return $next($request);
    }
}
