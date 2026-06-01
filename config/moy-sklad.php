<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Authenticate with a bearer token (recommended) OR login + password.
    | For multi-tenant apps, resolve a per-tenant client at runtime with
    | MoySklad::forToken($tenantToken) instead of these globals.
    |
    */
    'token' => env('MOYSKLAD_TOKEN'),
    'login' => env('MOYSKLAD_LOGIN'),
    'password' => env('MOYSKLAD_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Base URL & timeout
    |--------------------------------------------------------------------------
    */
    'base_url' => env('MOYSKLAD_BASE_URL', 'https://api.moysklad.ru/api/remap/1.2'),
    'timeout' => (int) env('MOYSKLAD_TIMEOUT', 30),
];
