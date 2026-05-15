<?php

return [
    'pathao' => [
        'client_id'     => env('PATHAO_CLIENT_ID'),
        'client_secret' => env('PATHAO_CLIENT_SECRET'),
        'base_url'      => env('PATHAO_BASE_URL', 'https://hermes-api.pathao.com'),
    ],

    'steadfast' => [
        'api_key'    => env('STEADFAST_API_KEY'),
        'secret_key' => env('STEADFAST_SECRET_KEY'),
        'base_url'   => env('STEADFAST_BASE_URL', 'https://portal.steadfast.com.bd/public/api/v1'),
    ],
];
