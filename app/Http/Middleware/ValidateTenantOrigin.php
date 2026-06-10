<?php

namespace App\Http\Middleware;

use App\Services\Cors\TenantOriginResolver;
use App\Support\AllowedOrigin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quando o tenant define allowed_origins em settings, bloqueia requisições
 * de origens não autorizadas (widget em domínio externo).
 */
class ValidateTenantOrigin
{
    public function __construct(private TenantOriginResolver $resolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $tenant = $this->resolver->resolveFromRequest($request);

        if ($tenant === null) {
            return $next($request);
        }

        $tenantOrigins = $tenant->getAllowedOrigins();

        if ($tenantOrigins === []) {
            return $next($request);
        }

        $allowedOrigins = AllowedOrigin::normalizeList(array_merge(
            $tenantOrigins,
            config('cors.allowed_origins', [])
        ));

        $requestOrigin = AllowedOrigin::normalize($request->headers->get('Origin'))
            ?? AllowedOrigin::fromReferer($request->headers->get('Referer'));

        if ($requestOrigin === null) {
            return $this->deny('Origem da requisição não informada.');
        }

        if (! AllowedOrigin::matches($requestOrigin, $allowedOrigins)) {
            return $this->deny('Origem não autorizada para este tenant.');
        }

        return $next($request);
    }

    private function deny(string $message): Response
    {
        return response()->json([
            'success' => false,
            'error'   => $message,
        ], 403);
    }
}
