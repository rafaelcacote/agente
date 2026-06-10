<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.email'    => 'admin@test.local',
            'admin.password' => 'secret-test-password',
        ]);
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_login_and_access_dashboard(): void
    {
        $this->post('/admin/login', [
            'email'    => 'admin@test.local',
            'password' => 'secret-test-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/admin')->assertOk();
    }

    public function test_admin_can_create_tenant(): void
    {
        $this->loginAsAdmin();

        $response = $this->post('/admin/tenants', [
            'name'          => 'Loja Teste',
            'slug'          => 'loja-teste',
            'is_active'     => '1',
            'system_prompt' => 'Você é o assistente da Loja Teste.',
            'allowed_origins' => "https://loja.test\n",
            'widget_primary_color' => '#112233',
            'widget_greeting' => 'Bem-vindo!',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'slug' => 'loja-teste',
            'name' => 'Loja Teste',
        ]);
    }

    public function test_admin_can_view_conversation_detail(): void
    {
        $this->seed(\Database\Seeders\TenantSeeder::class);

        $tenant = Tenant::query()->where('slug', 'default')->first();

        $conversation = Conversation::create([
            'tenant_id'  => $tenant->id,
            'source'     => 'test',
            'ip_address' => '127.0.0.1',
        ]);

        $this->loginAsAdmin();

        $this->get('/admin/conversations/'.$conversation->uuid)
            ->assertOk()
            ->assertSee($conversation->uuid);
    }

    private function loginAsAdmin(): void
    {
        $this->post('/admin/login', [
            'email'    => 'admin@test.local',
            'password' => 'secret-test-password',
        ]);
    }
}
