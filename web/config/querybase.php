<?php

return [

    'api_url' => env('QUERYBASE_API_URL', 'http://localhost:8080'),

    'encryption_key' => env('QUERYBASE_ENCRYPTION_KEY'),

    'connection_timeout' => env('QUERYBASE_CONNECTION_TIMEOUT', 30),

    'query_timeout' => env('QUERYBASE_QUERY_TIMEOUT', 120),

    'retry' => [
        'times' => env('QUERYBASE_RETRY_TIMES', 3),
        'sleep_ms' => env('QUERYBASE_RETRY_SLEEP_MS', 100),
    ],

    'cache' => [
        'default_ttl' => env('QUERYBASE_DEFAULT_CACHE_TTL', 300), // 5 minutos
        'max_ttl' => env('QUERYBASE_MAX_CACHE_TTL', 86400),       // 24 horas
    ],

];