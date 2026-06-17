<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Volpa Mail API Key
    |--------------------------------------------------------------------------
    |
    | Chave de API do tenant no Volpa Mail. Gerada no painel Filament em
    | Configurações > API Keys. Enviada no header "X-API-Key" de cada request.
    |
    */
    'api_key' => env('VOLPA_MAIL_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL da API
    |--------------------------------------------------------------------------
    |
    | Endpoint base da API REST do Volpa Mail (sem barra final). Inclui o
    | prefixo de versão (/v1).
    |
    */
    'base_url' => env('VOLPA_MAIL_BASE_URL', 'https://api.mail.volpa.com.br/v1'),

    /*
    |--------------------------------------------------------------------------
    | Timeout (segundos)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('VOLPA_MAIL_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Retries
    |--------------------------------------------------------------------------
    |
    | Número de tentativas e intervalo (ms) entre elas em caso de falha de
    | rede ou resposta 5xx.
    |
    */
    'retry' => [
        'times' => (int) env('VOLPA_MAIL_RETRY_TIMES', 2),
        'sleep' => (int) env('VOLPA_MAIL_RETRY_SLEEP', 200),
    ],

];
