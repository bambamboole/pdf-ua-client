<?php

declare(strict_types=1);

return [
    'base_url' => env('PDF_UA_API_URL', 'https://pdf-ua-api.c1.bambamboole.com'),
    'token' => env('PDF_UA_API_TOKEN'),
    'connect_timeout' => (int) env('PDF_UA_API_CONNECT_TIMEOUT', 5),
    'timeout' => (int) env('PDF_UA_API_TIMEOUT', 120),
];
