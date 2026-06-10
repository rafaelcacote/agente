<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Cria o tenant padrão e associa conversas antigas sem tenant_id.
     */
    public function run(): void
    {
        $slug = (string) config('openai.default_tenant_slug', 'default');

        $defaultOrigins = [
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            'http://localhost',
            'http://127.0.0.1',
        ];

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name'          => 'Empresa padrão (demo)',
                'system_prompt' => config('openai.system_prompt'),
                'is_active'     => true,
                'settings'      => [
                    'allowed_origins' => $defaultOrigins,
                ],
            ]
        );

        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        if (empty($settings['allowed_origins'])) {
            $tenant->update([
                'settings' => array_merge($settings, [
                    'allowed_origins' => $defaultOrigins,
                ]),
            ]);
        }

        if (! $tenant->hasApiKey()) {
            $plainKey = env('DEFAULT_TENANT_API_KEY');

            if (is_string($plainKey) && $plainKey !== '') {
                $tenant->setApiKeyFromPlain($plainKey);
            }
        }

        Conversation::query()->whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
    }
}
