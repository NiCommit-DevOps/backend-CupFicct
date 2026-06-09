<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal (CU05 — Procesar Pago de Inscripción)
    |--------------------------------------------------------------------------
    | Credenciales de la pasarela según el modo activo (sandbox/live). El cobro
    | del cupo de inscripción es en BOB, pero PayPal no opera esa divisa: se
    | cobra el equivalente en 'currency' aplicando 'bob_to_usd_rate'.
    */
    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'),

        'sandbox' => [
            'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID'),
            'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET'),
        ],

        'live' => [
            'client_id' => env('PAYPAL_LIVE_CLIENT_ID'),
            'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET'),
        ],

        // Divisa con la que se crea la orden en PayPal (BOB no está soportada).
        'currency' => env('PAYPAL_CURRENCY', 'USD'),

        // Costo del cupo de inscripción (moneda local, BOB).
        'inscripcion_monto_bob' => (float) env('PAYPAL_INSCRIPCION_MONTO', 700),

        // Tipo de cambio BOB→USD para convertir el monto al crear la orden.
        'bob_to_usd_rate' => (float) env('PAYPAL_BOB_USD_RATE', 6.96),
    ],

];
