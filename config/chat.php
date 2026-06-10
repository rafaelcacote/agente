<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Exigir API Key em todas as requisições do chat
    |--------------------------------------------------------------------------
    | Em produção, defina CHAT_REQUIRE_API_KEY=true.
    | Tenants com api_key_hash também exigem chave mesmo com esta opção false.
    */
    'require_api_key' => (bool) env('CHAT_REQUIRE_API_KEY', false),

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'per_minute' => (int) env('CHAT_RATE_LIMIT_PER_MINUTE', 30),
        'per_day'    => (int) env('CHAT_RATE_LIMIT_PER_DAY', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metadados da mensagem
    |--------------------------------------------------------------------------
    */
    'metadata' => [
        'max_keys'        => (int) env('CHAT_METADATA_MAX_KEYS', 10),
        'max_value_length' => (int) env('CHAT_METADATA_MAX_VALUE_LENGTH', 255),
    ],

];
