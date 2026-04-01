<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    | Chave de autenticação para a OpenAI API. Nunca exponha esta chave
    | em código-fonte ou logs. Use sempre variáveis de ambiente.
    */
    'api_key' => env('OPENAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Modelo padrão
    |--------------------------------------------------------------------------
    | Modelo utilizado nas chamadas à Responses API. Pode ser sobrescrito
    | por conversa futuramente (ex: gpt-4o, gpt-4o-mini, gpt-4-turbo).
    */
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    | URL base da API. Permite apontar para proxies ou ambientes alternativos
    | sem alterar o código da aplicação.
    */
    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | Timeout das requisições (segundos)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('OPENAI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Número máximo de tokens na resposta
    |--------------------------------------------------------------------------
    */
    'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 1024),

    /*
    |--------------------------------------------------------------------------
    | Temperatura (criatividade) — 0.0 a 2.0
    |--------------------------------------------------------------------------
    | Valores menores tornam as respostas mais deterministas e objetivas.
    | Para atendimento empresarial, valores entre 0.3 e 0.7 são adequados.
    */
    'temperature' => (float) env('OPENAI_TEMPERATURE', 0.5),

    /*
    |--------------------------------------------------------------------------
    | Limite de mensagens de contexto enviadas por requisição
    |--------------------------------------------------------------------------
    | Controla quantas mensagens anteriores da conversa são enviadas como
    | contexto. Aumentar melhora a coerência, mas consome mais tokens.
    */
    'context_messages_limit' => (int) env('OPENAI_CONTEXT_LIMIT', 20),

    /*
    |--------------------------------------------------------------------------
    | System Prompt padrão do agente
    |--------------------------------------------------------------------------
    | Instrução base que define o comportamento do assistente.
    | Pode ser sobrescrita por conversa futuramente para multi-tenancy.
    |
    | EXTENSÃO FUTURA: Substituir por lookup em banco de dados por cliente/tenant.
    */
    'system_prompt' => env('OPENAI_SYSTEM_PROMPT', <<<'PROMPT'
Você é um assistente virtual profissional de atendimento ao cliente.

Suas diretrizes de comportamento:
- Responda sempre de forma profissional, clara, objetiva e cordial.
- Nunca invente informações que não lhe foram fornecidas.
- Caso não saiba ou não tenha a informação solicitada, informe isso claramente ao cliente, sem tentar adivinhar.
- Quando a situação exigir atendimento especializado ou estiver fora do seu escopo, sugira educadamente o encaminhamento para um atendente humano.
- Mantenha um tom comercial e empático em todas as interações.
- Seja conciso: prefira respostas curtas e diretas, expandindo apenas quando necessário.
- Não repita a pergunta do usuário antes de responder.
PROMPT),

];
