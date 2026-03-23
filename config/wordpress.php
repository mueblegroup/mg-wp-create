<?php

return [

    'core_path' => env('WORDPRESS_CORE_PATH', '/usr/local/src/wordpress'),

    'wp_cli' => env('WP_CLI_BINARY', '/usr/local/bin/wp'),

    'sites_root' => env('WORDPRESS_SITES_ROOT', '/home'),

    'theme_storage_disk' => env('WORDPRESS_THEME_STORAGE_DISK', 'themes'),

    /*
    |--------------------------------------------------------------------------
    | Remote Theme Base Path
    |--------------------------------------------------------------------------
    |
    | This is the folder on the REMOTE Hestia server where your theme ZIP files
    | exist. Example:
    | /home/admin/theme-packages
    | /root/theme-zips
    |
    */
    'remote_theme_base_path' => env('WORDPRESS_REMOTE_THEME_BASE_PATH', '/home/admin/theme-packages'),

    'default_admin_role' => env('WORDPRESS_DEFAULT_ADMIN_ROLE', 'administrator'),
    'default_locale' => env('WORDPRESS_DEFAULT_LOCALE', 'en_US'),

];