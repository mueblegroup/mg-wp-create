<?php

return [

    'host' => env('REMOTE_HOST'),
    'port' => (int) env('REMOTE_PORT', 22),
    'username' => env('REMOTE_USERNAME'),
    'private_key_path' => env('REMOTE_PRIVATE_KEY_PATH'),
    'passphrase' => env('REMOTE_PRIVATE_KEY_PASSPHRASE'),
    'connect_timeout' => (int) env('REMOTE_CONNECT_TIMEOUT', 20),
    'command_timeout' => (int) env('REMOTE_COMMAND_TIMEOUT', 300),

];