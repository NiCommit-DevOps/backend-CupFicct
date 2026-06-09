<?php

/*
|--------------------------------------------------------------------------
| CORS — el frontend (otro dominio en Railway) consume el API por token JWT.
|--------------------------------------------------------------------------
| Define los orígenes permitidos con CORS_ALLOWED_ORIGINS (lista separada por
| comas) o deja '*' para permitir cualquiera. Como la autenticación es por
| Bearer token (no cookies), no se usan credenciales.
*/

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
