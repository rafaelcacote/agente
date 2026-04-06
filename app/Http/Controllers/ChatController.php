<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendChatMessageRequest;
use App\Models\Conversation;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ChatController extends Controller
{
    public function __construct(private ChatService $chatService)
    {
    }

    /**
     * Exibe a interface de teste do chat (rota web).
     */
    public function index(): View
    {
        return view('chat.test');
    }

    /**
     * Processa o envio de uma mensagem e retorna a resposta da IA (rota API).
     *
     * Fluxo:
     *  1. Valida a request via SendChatMessageRequest
     *  2. Delega toda a lógica ao ChatService
     *  3. Retorna resposta JSON formatada
     *  4. Trata erros de forma clara e sem expor stack traces ao cliente
     */
    public function send(SendChatMessageRequest $request): JsonResponse
    {
        try {
            $result = $this->chatService->sendMessage(
                userMessage:      $request->string('message')->trim()->value(),
                conversationUuid: $request->input('conversation_uuid'),
                source:           $request->input('source', 'web'),
                metadata:         $request->input('metadata', []),
                ipAddress:        $request->ip() ?? '',
                userAgent:        $request->userAgent(),
                tenantSlug:       $request->filled('tenant_slug')
                    ? $request->string('tenant_slug')->trim()->value()
                    : null,
            );

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);

        } catch (RuntimeException $e) {
            // Erros de negócio/integração: mensagem amigável exposta ao cliente
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            // Erros inesperados: não expor detalhes ao cliente
            return response()->json([
                'success' => false,
                'error'   => 'Ocorreu um erro interno. Por favor, tente novamente.',
            ], 500);
        }
    }

    /**
     * Retorna o histórico de mensagens de uma conversa pelo UUID público.
     */
    public function history(string $uuid): JsonResponse
    {
        $conversation = Conversation::where('uuid', $uuid)
            ->with([
                'tenant',
                'messages' => fn ($q) => $q->whereIn('role', ['user', 'assistant']),
            ])
            ->firstOrFail();

        $messages = $conversation->messages->map(fn ($msg) => [
            'id'         => $msg->id,
            'role'       => $msg->role,
            'content'    => $msg->content,
            'created_at' => $msg->created_at->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'conversation_uuid' => $conversation->uuid,
                'tenant_slug'       => $conversation->tenant?->slug,
                'status'            => $conversation->status,
                'messages'          => $messages,
            ],
        ]);
    }
}
