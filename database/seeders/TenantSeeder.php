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

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name'           => 'Empresa padrão (demo)',
                'system_prompt'  => config('openai.system_prompt'),
                'is_active'      => true,
            ]
        );

        Conversation::query()->whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
    }
}
