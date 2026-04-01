<?php

namespace App\Services\OpenAI;

use App\Models\Conversation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Responsável exclusivamente pela comunicação com a OpenAI API.
 *
 * Mantém-se agnóstico à lógica de negócio: recebe o payload formatado
 * e retorna a resposta estruturada. Toda persistência e orquestração
 * ficam no ChatService.
 *
 * EXTENSÃO FUTURA — Function Calling:
 *   Adicionar o método `withTools(array $tools)` e passar `tools` no payload.
 *   A resposta pode conter `tool_calls` que devem ser tratados no ChatService.
 *
 * EXTENSÃO FUTURA — File Search / Assistants API:
 *   Criar um método `sendToAssistant()` que usa o endpoint /threads e /runs
 *   em vez de /chat/completions.
 */
class OpenAIService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private int $timeout;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $this->apiKey      = config('openai.api_key');
        $this->baseUrl     = rtrim(config('openai.base_url'), '/');
        $this->model       = config('openai.model');
        $this->timeout     = config('openai.timeout');
        $this->maxTokens   = config('openai.max_tokens');
        $this->temperature = config('openai.temperature');

        if (empty($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY não configurada no .env');
        }
    }

    /**
     * Envia uma lista de mensagens para a OpenAI e retorna a resposta estruturada.
     *
     * @param  array  $messages  Array no formato [['role' => '...', 'content' => '...']]
     * @param  string|null  $systemPrompt  Instrução de sistema (sobrescreve o padrão da config)
     * @return array{content: string, model: string, prompt_tokens: int, completion_tokens: int, total_tokens: int, raw: array}
     *
     * @throws RuntimeException em caso de falha na API
     */
    public function chat(array $messages, ?string $systemPrompt = null): array
    {
        $payload = $this->buildPayload($messages, $systemPrompt);

        $this->logRequest($payload);

        try {
            $response = Http::withToken($this->apiKey)
                ->baseUrl($this->baseUrl)
                ->timeout($this->timeout)
                ->acceptJson()
                ->post('/chat/completions', $payload);

            $response->throw();

            $data = $response->json();

            $this->logResponse($data);

            return $this->parseResponse($data);

        } catch (ConnectionException $e) {
            Log::error('[OpenAI] Falha de conexão', ['error' => $e->getMessage()]);
            throw new RuntimeException('Não foi possível conectar à OpenAI. Tente novamente em instantes.');

        } catch (RequestException $e) {
            $status = $e->response->status();
            $body   = $e->response->json();

            Log::error('[OpenAI] Erro na requisição', [
                'status' => $status,
                'error'  => $body['error']['message'] ?? 'Erro desconhecido',
            ]);

            $this->throwFriendlyException($status, $body);
        }
    }

    // -------------------------------------------------------------------------
    // Métodos privados
    // -------------------------------------------------------------------------

    private function buildPayload(array $messages, ?string $systemPrompt): array
    {
        $systemInstruction = $systemPrompt ?? config('openai.system_prompt');

        // O system prompt vai sempre como primeira mensagem
        $fullMessages = array_merge(
            [['role' => 'system', 'content' => $systemInstruction]],
            $messages
        );

        return [
            'model'       => $this->model,
            'messages'    => $fullMessages,
            'max_tokens'  => $this->maxTokens,
            'temperature' => $this->temperature,
            // EXTENSÃO FUTURA: adicionar 'tools' e 'tool_choice' aqui para function calling
        ];
    }

    private function parseResponse(array $data): array
    {
        $choice  = $data['choices'][0] ?? null;
        $usage   = $data['usage'] ?? [];

        if (! $choice || empty($choice['message']['content'])) {
            Log::error('[OpenAI] Resposta sem conteúdo válido', ['data' => $data]);
            throw new RuntimeException('A IA retornou uma resposta inválida. Tente novamente.');
        }

        return [
            'content'           => trim($choice['message']['content']),
            'model'             => $data['model'] ?? $this->model,
            'prompt_tokens'     => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens'      => $usage['total_tokens'] ?? 0,
            'raw'               => $data,
        ];
    }

    /**
     * Loga o payload sem expor a API key ou conteúdos sensíveis em produção.
     */
    private function logRequest(array $payload): void
    {
        Log::info('[OpenAI] Enviando requisição', [
            'model'           => $payload['model'],
            'message_count'   => count($payload['messages']),
            'max_tokens'      => $payload['max_tokens'],
            'temperature'     => $payload['temperature'],
        ]);
    }

    private function logResponse(array $data): void
    {
        Log::info('[OpenAI] Resposta recebida', [
            'model'             => $data['model'] ?? null,
            'prompt_tokens'     => $data['usage']['prompt_tokens'] ?? null,
            'completion_tokens' => $data['usage']['completion_tokens'] ?? null,
            'total_tokens'      => $data['usage']['total_tokens'] ?? null,
            'finish_reason'     => $data['choices'][0]['finish_reason'] ?? null,
        ]);
    }

    private function throwFriendlyException(int $status, array $body): never
    {
        $message = match ($status) {
            401 => 'Chave de API inválida. Verifique as configurações.',
            429 => 'Limite de requisições atingido. Aguarde um momento.',
            500, 503 => 'O serviço de IA está temporariamente indisponível.',
            default => 'Erro na comunicação com a IA: ' . ($body['error']['message'] ?? "status $status"),
        };

        throw new RuntimeException($message);
    }
}
