<?php

declare(strict_types=1);

return [
    'base_url' => env('PDF_UA_API_URL', 'http://pdf-ua-api:8888'),
    'token' => env('PDF_UA_API_TOKEN'),
    'timeout' => env('PDF_UA_API_TIMEOUT', 30),
    'retry' => [
        'attempts' => env('PDF_UA_API_RETRY_ATTEMPTS', 2),
        'sleep_ms' => env('PDF_UA_API_RETRY_SLEEP_MS', 100),
    ],
];
