<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatCorsTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'ag_test_cors_key';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\TenantSeeder::class);

        Tenant::query()->where('slug', 'default')->first()?->setApiKeyFromPlain(self::API_KEY);
    }

    /** @return array<string, string> */
    private function chatHeaders(array $extra = []): array
    {
        return array_merge([
            'X-Agent-Key'   => self::API_KEY,
            'X-Tenant-Slug' => 'default',
        ], $extra);
    }

    public function test_preflight_returns_cors_headers_for_allowed_origin(): void
    {
        $response = $this->call(
            'OPTIONS',
            '/api/chat/message',
            [],
            [],
            [],
            [
                'HTTP_ORIGIN' => 'http://localhost:8000',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
            ]
        );

        $response->assertOk();
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:8000');
    }

    public function test_post_is_allowed_from_configured_tenant_origin(): void
    {
        $response = $this->postJson('/api/chat/message', [
            'message'     => 'qual o prazo de entrega?',
            'tenant_slug' => 'default',
            'source'      => 'test',
        ], $this->chatHeaders([
            'Origin' => 'http://127.0.0.1:8000',
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:8000');
    }

    public function test_post_is_blocked_from_unauthorized_origin_when_tenant_restricts(): void
    {
        Tenant::query()->where('slug', 'default')->update([
            'settings' => [
                'allowed_origins' => ['http://loja-autorizada.test'],
            ],
        ]);

        $response = $this->postJson('/api/chat/message', [
            'message'     => 'Olá',
            'tenant_slug' => 'default',
        ], $this->chatHeaders([
            'Origin' => 'http://site-malicioso.test',
        ]));

        $response->assertForbidden();
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Origem não autorizada para este tenant.');
    }

    public function test_post_without_origin_is_blocked_when_tenant_restricts(): void
    {
        Tenant::query()->where('slug', 'default')->update([
            'settings' => [
                'allowed_origins' => ['http://loja-autorizada.test'],
            ],
        ]);

        $response = $this->postJson('/api/chat/message', [
            'message'     => 'Olá',
            'tenant_slug' => 'default',
        ], $this->chatHeaders());

        $response->assertForbidden();
        $response->assertJsonPath('error', 'Origem da requisição não informada.');
    }

    public function test_tenant_without_allowed_origins_accepts_any_origin(): void
    {
        Tenant::query()->where('slug', 'default')->update([
            'settings' => [],
        ]);

        $response = $this->postJson('/api/chat/message', [
            'message'     => 'qual o prazo?',
            'tenant_slug' => 'default',
        ], $this->chatHeaders([
            'Origin' => 'http://qualquer-site.test',
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }
}
