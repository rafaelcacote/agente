<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const DEV_API_KEY = 'ag_test_key_for_security_tests';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\TenantSeeder::class);

        Tenant::query()->where('slug', 'default')->first()?->setApiKeyFromPlain(self::DEV_API_KEY);
    }

    public function test_request_without_api_key_returns_unauthorized_when_tenant_has_key(): void
    {
        $response = $this->postJson('/api/chat/message', [
            'message'     => 'qual o prazo?',
            'tenant_slug' => 'default',
        ], [
            'Origin' => 'http://127.0.0.1:8000',
        ]);

        $response->assertUnauthorized();
        $response->assertJsonPath('error', 'API Key não informada.');
    }

    public function test_request_with_valid_api_key_is_accepted(): void
    {
        $response = $this->postJson('/api/chat/message', [
            'message'     => 'qual o prazo?',
            'tenant_slug' => 'default',
        ], [
            'Origin'        => 'http://127.0.0.1:8000',
            'X-Agent-Key'   => self::DEV_API_KEY,
            'X-Tenant-Slug' => 'default',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_request_with_invalid_api_key_returns_unauthorized(): void
    {
        $response = $this->postJson('/api/chat/message', [
            'message'     => 'ola',
            'tenant_slug' => 'default',
        ], [
            'Origin'        => 'http://127.0.0.1:8000',
            'X-Agent-Key'   => 'ag_invalid_key',
            'X-Tenant-Slug' => 'default',
        ]);

        $response->assertUnauthorized();
        $response->assertJsonPath('error', 'API Key inválida.');
    }

    public function test_rate_limit_returns_429_with_clear_message(): void
    {
        config(['chat.rate_limit.per_minute' => 2]);

        $headers = [
            'Origin'        => 'http://127.0.0.1:8000',
            'X-Agent-Key'   => self::DEV_API_KEY,
            'X-Tenant-Slug' => 'default',
        ];

        $this->postJson('/api/chat/message', [
            'message'     => 'qual o prazo?',
            'tenant_slug' => 'default',
        ], $headers)->assertOk();

        $this->postJson('/api/chat/message', [
            'message'     => 'qual o prazo de entrega?',
            'tenant_slug' => 'default',
        ], $headers)->assertOk();

        $response = $this->postJson('/api/chat/message', [
            'message'     => 'mais uma',
            'tenant_slug' => 'default',
        ], $headers);

        $response->assertStatus(429);
        $response->assertJsonPath('error', 'Muitas requisições. Aguarde um momento antes de tentar novamente.');
    }

    public function test_development_routes_are_blocked_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('/chat/test')->assertNotFound();
        $this->get('/widget/demo')->assertNotFound();
    }

    public function test_metadata_is_sanitized(): void
    {
        $response = $this->postJson('/api/chat/message', [
            'message'     => 'qual o prazo?',
            'tenant_slug' => 'default',
            'metadata'    => [
                'nome' => '<script>alert(1)</script>João',
                'page' => 'https://loja.com/contato',
            ],
        ], [
            'Origin'        => 'http://127.0.0.1:8000',
            'X-Agent-Key'   => self::DEV_API_KEY,
            'X-Tenant-Slug' => 'default',
        ]);

        $response->assertOk();

        $uuid = $response->json('data.conversation_uuid');

        $this->assertDatabaseHas('conversations', [
            'uuid' => $uuid,
        ]);

        $conversation = \App\Models\Conversation::query()->where('uuid', $uuid)->first();

        $this->assertSame('alert(1)João', $conversation?->metadata['nome'] ?? null);
    }
}
