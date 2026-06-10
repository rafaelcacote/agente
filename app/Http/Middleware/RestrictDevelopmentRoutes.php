<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia rotas de desenvolvimento/demo quando APP_ENV=production.
 */
class RestrictDevelopmentRoutes
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            abort(404);
        }

        return $next($request);
    }
}
