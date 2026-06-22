<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Volpa Mail API Key
    |--------------------------------------------------------------------------
    |
    | Tenant API key for Volpa Mail. Generated in the Filament panel under
    | Settings > API Keys. Sent in the "X-API-Key" header of every request.
    |
    */
    'api_key' => env('VOLPA_MAIL_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | Base endpoint of the Volpa Mail REST API (no trailing slash). Includes
    | the version prefix (/v1).
    |
    */
    'base_url' => env('VOLPA_MAIL_BASE_URL', 'https://mail.volpa.com.br/v1'),

    /*
    |--------------------------------------------------------------------------
    | Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('VOLPA_MAIL_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Retries
    |--------------------------------------------------------------------------
    |
    | Number of attempts and the interval (ms) between them on network
    | failure or a 5xx response.
    |
    */
    'retry' => [
        'times' => (int) env('VOLPA_MAIL_RETRY_TIMES', 2),
        'sleep' => (int) env('VOLPA_MAIL_RETRY_SLEEP', 200),
    ],

];
