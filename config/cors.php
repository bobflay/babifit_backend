<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The babifit API lives under the `v1` prefix, so it must be listed here for
    | the framework's HandleCors middleware to emit the Access-Control-* headers
    | (without it, Flutter Web / browser clients are blocked by CORS).
    |
    | Auth uses Bearer tokens (not cookies), so a wildcard origin is fine for
    | development and `supports_credentials` stays false. Lock `allowed_origins`
    | down to your real web origin(s) for production.
    |
    */

    'paths' => ['api/*', 'v1/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
