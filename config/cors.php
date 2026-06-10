<?php

$origins = env('CORS_ALLOWED_ORIGINS', 'http://localhost:8000,http://127.0.0.1:8000');

return [

    /*
    |--------------------------------------------------------------------------
    | Rotas com CORS habilitado
    |--------------------------------------------------------------------------
    | Apenas as rotas do chat agent usam CORS (widget embeddable).
    | Origens de tenants são mescladas em runtime via MergeChatCorsOrigins.
    */
    'paths' => ['api/chat/*'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        is_array($origins) ? $origins : explode(',', (string) $origins)
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Accept',
        'Origin',
        'X-Tenant-Slug',
        'X-Agent-Key',
    ],

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', 86400),

    'supports_credentials' => false,

];
