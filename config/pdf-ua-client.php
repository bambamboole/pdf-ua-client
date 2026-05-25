<?php

declare(strict_types=1);

return [
    'base_url' => env('PDF_UA_API_URL', 'http://127.0.0.1:8888'),
    'token' => env('PDF_UA_API_TOKEN'),
    'timeout' => env('PDF_UA_API_TIMEOUT', 30),
    'retry' => [
        'attempts' => env('PDF_UA_API_RETRY_ATTEMPTS', 2),
        'sleep_ms' => env('PDF_UA_API_RETRY_SLEEP_MS', 100),
    ],
    'fonts' => [
        'Inter' => [
            'label' => 'Inter',
            'family' => 'Inter',
            'url' => 'https://cdn.jsdelivr.net/npm/@fontsource-variable/inter@5.2.8/files/inter-latin-wght-normal.woff2',
            'weight' => '100 900',
            'style' => 'normal',
            'display' => 'swap',
            'format' => 'woff2',
        ],
        'Georgia' => [
            'label' => 'Georgia',
            'family' => 'Georgia',
        ],
    ],
];
