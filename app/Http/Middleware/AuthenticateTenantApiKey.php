<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Cors\TenantOriginResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateTenantApiKey
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

        if ($tenant !== null) {
            $request->attributes->set('tenant', $tenant);
        }

        if (! $this->mustAuthenticate($tenant)) {
            return $next($request);
        }

        $apiKey = $this->extractApiKey($request);

        if ($apiKey === null || $apiKey === '') {
            return $this->unauthorized('API Key não informada.');
        }

        if ($tenant === null) {
            return $this->unauthorized('Tenant não identificado para validar a API Key.');
        }

        if (! $tenant->verifyApiKey($apiKey)) {
            return $this->unauthorized('API Key inválida.');
        }

        return $next($request);
    }

    private function mustAuthenticate(?Tenant $tenant): bool
    {
        if (config('chat.require_api_key', false)) {
            return true;
        }

        return $tenant !== null && $tenant->hasApiKey();
    }

    private function extractApiKey(Request $request): ?string
    {
        $header = $request->header('X-Agent-Key');

        if (is_string($header) && $header !== '') {
            return trim($header);
        }

        $query = $request->query('api_key');

        return is_string($query) && $query !== '' ? trim($query) : null;
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'error'   => $message,
        ], 401);
    }
}
