<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\OpenAI\OpenAIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Orquestra o fluxo completo de uma interação de chat:
 *   1. Localiza ou cria a conversa
 *   2. Persiste a mensagem do usuário
 *   3. Monta o contexto e chama a OpenAI via OpenAIService
 *   4. Persiste a resposta do assistente
 *   5. Retorna os dados formatados para o Controller
 *
 * Este serviço é o único ponto de contato entre Controller e OpenAIService.
 *
 * EXTENSÃO FUTURA — Jobs assíncronos:
 *   Mover a chamada à OpenAI para um Job (ProcessChatMessage) usando queue,
 *   retornando um job_id ao frontend que faz polling ou usa WebSocket.
 */
class ChatService
{
    private int $contextLimit;

    public function __construct(private OpenAIService $openAI)
    {
        $this->contextLimit = config('openai.context_messages_limit', 20);
    }

    /**
     * Processa o envio de uma mensagem e retorna a resposta do assistente.
     *
     * @param  string  $userMessage  Texto enviado pelo usuário
     * @param  string|null  $conversationUuid  UUID de conversa existente ou null para nova
     * @param  string  $source  Canal de origem (web, api, whatsapp...)
     * @param  array  $metadata  Dados adicionais (nome, email, página...)
     * @param  string  $ipAddress  IP do cliente
     * @param  string|null  $userAgent  User-agent do cliente
     * @param  string|null  $tenantSlug  Slug da empresa (omitir na continuação da mesma conversa)
     * @return array{conversation_uuid: string, tenant_slug: string|null, message: array, reply: array}
     *
     * @throws RuntimeException
     */
    public function sendMessage(
        string $userMessage,
        ?string $conversationUuid,
        string $source = 'web',
        array $metadata = [],
        string $ipAddress = '',
        ?string $userAgent = null,
        ?string $tenantSlug = null,
    ): array {
        return DB::transaction(function () use (
            $userMessage, $conversationUuid, $source, $metadata, $ipAddress, $userAgent, $tenantSlug
        ) {
            $conversation = $this->resolveConversation(
                $conversationUuid, $tenantSlug, $source, $metadata, $ipAddress, $userAgent
            );

            $conversation->loadMissing('tenant');

            $userMsg = $this->persistUserMessage($conversation, $userMessage);

            $ruleBasedReply = $this->getRuleBasedReply($userMessage);

            if ($ruleBasedReply !== null) {
                $assistantMsg = $this->persistAssistantMessage($conversation, [
                    'content'           => $ruleBasedReply,
                    'model'             => 'rule-based-faq',
                    'prompt_tokens'     => 0,
                    'completion_tokens' => 0,
                    'total_tokens'      => 0,
                    'raw'               => ['source' => 'faq_match'],
                ]);

                return [
                    'conversation_uuid' => $conversation->uuid,
                    'tenant_slug'       => $conversation->tenant?->slug,
                    'message'           => [
                        'id'         => $userMsg->id,
                        'role'       => $userMsg->role,
                        'content'    => $userMsg->content,
                        'created_at' => $userMsg->created_at->toIso8601String(),
                    ],
                    'reply' => [
                        'id'      => $assistantMsg->id,
                        'role'    => $assistantMsg->role,
                        'content' => $assistantMsg->content,
                        'model'   => $assistantMsg->model,
                        'tokens'  => [
                            'prompt'     => $assistantMsg->prompt_tokens,
                            'completion' => $assistantMsg->completion_tokens,
                            'total'      => $assistantMsg->total_tokens,
                        ],
                        'created_at' => $assistantMsg->created_at->toIso8601String(),
                    ],
                ];
            }

            $contextMessages = $conversation->getContextMessages($this->contextLimit);
            $openAIMessages  = $contextMessages->map->toOpenAIFormat()->values()->all();

            Log::info('[Chat] Enviando para OpenAI', [
                'conversation_uuid' => $conversation->uuid,
                'context_count'     => count($openAIMessages),
            ]);

            try {
                $aiResponse = $this->openAI->chat(
                    $openAIMessages,
                    $conversation->system_prompt
                );
            } catch (Throwable $e) {
                Log::error('[Chat] Falha ao chamar OpenAI', [
                    'conversation_uuid' => $conversation->uuid,
                    'error'             => $e->getMessage(),
                ]);
                throw $e;
            }

            $assistantMsg = $this->persistAssistantMessage($conversation, $aiResponse);

            return [
                'conversation_uuid' => $conversation->uuid,
                'tenant_slug'       => $conversation->tenant?->slug,
                'message'           => [
                    'id'         => $userMsg->id,
                    'role'       => $userMsg->role,
                    'content'    => $userMsg->content,
                    'created_at' => $userMsg->created_at->toIso8601String(),
                ],
                'reply' => [
                    'id'      => $assistantMsg->id,
                    'role'    => $assistantMsg->role,
                    'content' => $assistantMsg->content,
                    'model'   => $assistantMsg->model,
                    'tokens'  => [
                        'prompt'     => $assistantMsg->prompt_tokens,
                        'completion' => $assistantMsg->completion_tokens,
                        'total'      => $assistantMsg->total_tokens,
                    ],
                    'created_at' => $assistantMsg->created_at->toIso8601String(),
                ],
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Métodos privados
    // -------------------------------------------------------------------------

    private function resolveConversation(
        ?string $uuid,
        ?string $requestedTenantSlug,
        string $source,
        array $metadata,
        string $ipAddress,
        ?string $userAgent,
    ): Conversation {
        if ($uuid) {
            $conversation = Conversation::query()
                ->where('uuid', $uuid)
                ->active()
                ->lockForUpdate()
                ->with('tenant')
                ->firstOrFail();

            if ($requestedTenantSlug !== null && $requestedTenantSlug !== '') {
                $conversation->loadMissing('tenant');
                if ($conversation->tenant && $conversation->tenant->slug !== $requestedTenantSlug) {
                    throw new RuntimeException('Esta conversa não pertence ao tenant indicado.');
                }
            }

            Log::info('[Chat] Conversa existente localizada', [
                'uuid'      => $conversation->uuid,
                'tenant_id' => $conversation->tenant_id,
            ]);

            return $conversation;
        }

        $tenant = $this->resolveTenant($requestedTenantSlug);

        $systemPrompt = $tenant->system_prompt ?: config('openai.system_prompt');

        $conversation = Conversation::create([
            'tenant_id'     => $tenant->id,
            'source'        => $source,
            'ip_address'    => $ipAddress,
            'user_agent'    => $userAgent,
            'metadata'      => $metadata ?: null,
            'model'         => config('openai.model'),
            'system_prompt' => $systemPrompt,
        ]);

        Log::info('[Chat] Nova conversa criada', [
            'uuid'        => $conversation->uuid,
            'tenant_id'   => $tenant->id,
            'tenant_slug' => $tenant->slug,
        ]);

        return $conversation;
    }

    private function resolveTenant(?string $slug): Tenant
    {
        $slug = ($slug !== null && $slug !== '')
            ? $slug
            : (string) config('openai.default_tenant_slug', 'default');

        $tenant = Tenant::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $tenant) {
            throw new RuntimeException('Tenant não encontrado ou inativo.');
        }

        return $tenant;
    }

    private function persistUserMessage(Conversation $conversation, string $content): Message
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $content,
        ]);
    }

    private function persistAssistantMessage(Conversation $conversation, array $aiResponse): Message
    {
        return Message::create([
            'conversation_id'  => $conversation->id,
            'role'             => 'assistant',
            'content'          => $aiResponse['content'],
            'model'            => $aiResponse['model'],
            'prompt_tokens'    => $aiResponse['prompt_tokens'],
            'completion_tokens' => $aiResponse['completion_tokens'],
            'total_tokens'     => $aiResponse['total_tokens'],
            'raw_response'     => $aiResponse['raw'],
        ]);
    }

    private function getRuleBasedReply(string $userMessage): ?string
    {
        $normalized = Str::lower(Str::ascii($userMessage));

        if (preg_match('/\b(prazo|tempo|entrega)\b/', $normalized)) {
            return 'Depende do projeto, mas geralmente entre 15 a 60 dias.';
        }

        if (preg_match('/\b(contato|falar|telefone|whatsapp|email)\b/', $normalized)) {
            return 'Voce pode deixar seu nome e telefone que um especialista entrara em contato.';
        }

        if (preg_match('/\b(servico|servicos|oferecem|fazem)\b/', $normalized)) {
            return 'Oferecemos desenvolvimento de sistemas web personalizados, aplicativos e solucoes empresariais.';
        }

        return null;
    }
}
