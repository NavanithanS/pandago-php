<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pandago API Client ID
    |--------------------------------------------------------------------------
    |
    | Your pandago API client ID.
    | Format: pandago:my:00000000-0000-0000-0000-000000000000
    |
    */
    'client_id'   => env('PANDAGO_CLIENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Pandago API Key ID
    |--------------------------------------------------------------------------
    |
    | Your public key identifier provided by pandago.
    |
    */
    'key_id'      => env('PANDAGO_KEY_ID'),

    /*
    |--------------------------------------------------------------------------
    | Pandago API Scope
    |--------------------------------------------------------------------------
    |
    | Access scope of your service.
    | Format: pandago.api.my.*
    |
    */
    'scope'       => env('PANDAGO_SCOPE', 'pandago.api.my.*'),

    /*
    |--------------------------------------------------------------------------
    | Pandago API Private Key
    |--------------------------------------------------------------------------
    |
    | Your private key in PEM format.
    | You can use env('PANDAGO_PRIVATE_KEY') or a path to a file:
    | file_get_contents(storage_path('keys/pandago.pem'))
    |
    */
    'private_key'   => file_get_contents(env('PANDAGO_PRIVATE_KEY')),
    /*
    |--------------------------------------------------------------------------
    | Pandago API Country
    |--------------------------------------------------------------------------
    |
    | The country code to use for the API.
    | Available options: sg (Singapore), hk (Hong Kong), my (Malaysia),
    | th (Thailand), ph (Philippines), tw (Taiwan), pk (Pakistan),
    | jo (Jordan), fi (Finland), kw (Kuwait), no (Norway), se (Sweden)
    |
    */
    'country'     => env('PANDAGO_COUNTRY', 'my'),

    /*
    |--------------------------------------------------------------------------
    | Pandago API Environment
    |--------------------------------------------------------------------------
    |
    | The environment to use for the API.
    | Available options: sandbox, production
    |
    */
    'environment' => env('PANDAGO_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Pandago API Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout for API requests in seconds.
    |
    */
    'timeout'     => env('PANDAGO_TIMEOUT', 30),
];
