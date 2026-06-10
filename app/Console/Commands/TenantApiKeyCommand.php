<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantApiKeyCommand extends Command
{
    protected $signature = 'tenant:api-key
                            {slug : Slug do tenant}
                            {--rotate : Gera uma nova chave e invalida a anterior}';

    protected $description = 'Gera ou rotaciona a API Key pública de um tenant';

    public function handle(): int
    {
        $tenant = Tenant::query()
            ->where('slug', $this->argument('slug'))
            ->first();

        if ($tenant === null) {
            $this->error('Tenant não encontrado.');

            return self::FAILURE;
        }

        if ($tenant->hasApiKey() && ! $this->option('rotate')) {
            $this->warn("O tenant [{$tenant->slug}] já possui API Key.");
            $this->line('Use --rotate para gerar uma nova chave.');
            $this->line('Prefixo atual: '.($tenant->api_key_prefix ?? '—'));

            return self::SUCCESS;
        }

        $plainKey = $tenant->assignApiKey();

        $this->info("API Key gerada para [{$tenant->slug}]:");
        $this->newLine();
        $this->line($plainKey);
        $this->newLine();
        $this->comment('Guarde esta chave — ela não será exibida novamente.');
        $this->comment('No widget: data-api-key="'.$plainKey.'"');
        $this->comment('Na API: header X-Agent-Key: '.$plainKey);

        return self::SUCCESS;
    }
}
