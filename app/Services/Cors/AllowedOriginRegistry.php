<?php

namespace App\Services\Cors;

use App\Models\Tenant;
use App\Support\AllowedOrigin;
use Illuminate\Support\Facades\Cache;

class AllowedOriginRegistry
{
    private const CACHE_KEY = 'agente.tenant_allowed_origins';

    /**
     * União de todas as origens permitidas em tenants ativos (para preflight CORS).
     *
     * @return array<int, string>
     */
    public function allTenantOrigins(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            return Tenant::query()
                ->active()
                ->pluck('settings')
                ->flatMap(function ($settings) {
                    if (! is_array($settings)) {
                        return [];
                    }

                    $origins = $settings['allowed_origins'] ?? [];

                    return is_array($origins) ? $origins : [];
                })
                ->pipe(fn ($origins) => AllowedOrigin::normalizeList($origins->all()));
        });
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
