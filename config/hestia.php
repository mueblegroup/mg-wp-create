<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hestia API Endpoint
    |--------------------------------------------------------------------------
    |
    | Use the full API endpoint, usually something like:
    | https://panel.example.com:8083/api/
    | or whatever your panel is configured to use.
    |
    */
    'api_url' => env('HESTIA_API_URL'),

    /*
    |--------------------------------------------------------------------------
    | Access Keys
    |--------------------------------------------------------------------------
    |
    | Hestia recommends access_key + secret_key instead of legacy user/password.
    |
    */
    'access_key' => env('HESTIA_ACCESS_KEY'),
    'secret_key' => env('HESTIA_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | SSL Verification
    |--------------------------------------------------------------------------
    |
    | Keep true in production. You can disable only for development if your
    | panel certificate is self-signed.
    |
    */
    'verify_ssl' => filter_var(env('HESTIA_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Return Code Mode
    |--------------------------------------------------------------------------
    |
    | Hestia supports returncode yes/no.
    | We use "no" so we can read body + headers and inspect hestia-exit-code.
    |
    */
    'returncode' => env('HESTIA_RETURN_CODE', 'no'),

    /*
    |--------------------------------------------------------------------------
    | Package Map
    |--------------------------------------------------------------------------
    */
    'packages' => [
        'bronze' => env('HESTIA_PACKAGE_BRONZE', 'bronze'),
        'silver' => env('HESTIA_PACKAGE_SILVER', 'silver'),
        'gold'   => env('HESTIA_PACKAGE_GOLD', 'gold'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Web IP
    |--------------------------------------------------------------------------
    |
    | Hestia v-add-web-domain accepts USER DOMAIN [IP] ...
    | Use "default" unless you specifically want another IP.
    |
    */
    'default_web_ip' => env('HESTIA_DEFAULT_WEB_IP', '148.135.137.250'),

];