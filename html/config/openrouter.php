<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenRouter.ai API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for OpenRouter.ai API integration.
    |
    */

    'api_url' => env('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1'),

    'chat_endpoint' => env('OPENROUTER_CHAT_ENDPOINT', '/chat/completions'),

    'api_token' => env('OPENROUTER_API_TOKEN'),

    'default_model' => env('OPENROUTER_DEFAULT_MODEL', 'tngtech/deepseek-r1t2-chimera:free'),

    'timeout' => env('OPENROUTER_TIMEOUT', 30),

    'max_retries' => env('OPENROUTER_MAX_RETRIES', 3),

    'jobs' => [
        'getDeliveryDate' => [
            'regions' => [24, 38, 03, 75, 17, 28, 27, 25, 79],
            'min_price' => '1000000',
            'last_days' => 3
        ],
    ]
];
