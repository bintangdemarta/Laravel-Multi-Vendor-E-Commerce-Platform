<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    */
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
        'is_3ds' => env('MIDTRANS_IS_3DS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | RajaOngkir Configuration
    |--------------------------------------------------------------------------
    */
    'rajaongkir' => [
        'api_key' => env('RAJAONGKIR_API_KEY'),
        'type' => env('RAJAONGKIR_TYPE', 'pro'), // starter, basic, pro
        'base_url' => env('RAJAONGKIR_BASE_URL', 'https://pro.rajaongkir.com/api'),
    ],
];
