<?php

namespace App\Providers;

use App\Services\Cors\TenantOriginResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('chat', function (Request $request) {
            return Limit::perMinute(config('chat.rate_limit.per_minute', 30))
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'success' => false,
                    'error'   => 'Muitas requisições. Aguarde um momento antes de tentar novamente.',
                ], 429));
        });

        RateLimiter::for('chat-tenant', function (Request $request) {
            $tenant = app(TenantOriginResolver::class)->resolveFromRequest($request);
            $key = $tenant ? 'tenant:'.$tenant->slug : 'tenant:anonymous';

            return Limit::perDay(config('chat.rate_limit.per_day', 500))
                ->by($key)
                ->response(fn () => response()->json([
                    'success' => false,
                    'error'   => 'Limite diário de mensagens atingido para este agente.',
                ], 429));
        });
    }
}
