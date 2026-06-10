<?php

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_messages_are_in_chronological_order(): void
    {
        $tenant = Tenant::create([
            'name'      => 'Test',
            'slug'      => 'test',
            'is_active' => true,
        ]);

        $conversation = Conversation::create([
            'tenant_id' => $tenant->id,
            'source'    => 'test',
        ]);

        $pairs = [
            ['user', 'qual o horario agora'],
            ['assistant', 'Resposta sobre horario'],
            ['user', 'este site é mockado?'],
            ['assistant', 'Resposta sobre mock'],
        ];

        foreach ($pairs as [$role, $content]) {
            Message::create([
                'conversation_id' => $conversation->id,
                'role'            => $role,
                'content'         => $content,
            ]);
        }

        $context = $conversation->getContextMessages(10);

        $this->assertCount(4, $context);
        $this->assertSame('qual o horario agora', $context[0]->content);
        $this->assertSame('user', $context[0]->role);
        $this->assertSame('Resposta sobre horario', $context[1]->content);
        $this->assertSame('este site é mockado?', $context[2]->content);
        $this->assertSame('Resposta sobre mock', $context[3]->content);
        $this->assertSame('user', $context[2]->role);
    }
}
