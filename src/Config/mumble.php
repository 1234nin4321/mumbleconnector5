<?php

return [


    /*
    |--------------------------------------------------------------------------
    | Mumble Server Display Info
    |--------------------------------------------------------------------------
    |
    | Server address and port shown to users for connecting.
    |
    */

    'server' => [
        'address' => env('MUMBLE_SERVER_ADDRESS', 'mumble.example.com'),
        'port' => env('MUMBLE_SERVER_PORT', 64738),
    ],



    /*
    |--------------------------------------------------------------------------
    | REST API Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for connecting via REST API wrapper.
    | Recommended: https://github.com/alfg/murmur-rest
    |
    */

    'rest' => [
        'url' => env('MUMBLE_REST_URL', 'http://127.0.0.1:8080'),
        'api_key' => env('MUMBLE_REST_API_KEY', ''),
        'server_id' => env('MUMBLE_REST_SERVER_ID', 1),
        'timeout' => env('MUMBLE_REST_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Sync Settings
    |--------------------------------------------------------------------------
    */

    'sync' => [
        // Enable automatic sync
        'enabled' => env('MUMBLE_SYNC_ENABLED', true),

        // Sync interval in minutes (for scheduled task)
        'interval' => env('MUMBLE_SYNC_INTERVAL', 60),

        // Username format: 'character_name' or 'main_character'
        'username_format' => env('MUMBLE_USERNAME_FORMAT', 'main_character'),

        // Auto-remove users who are no longer in SeAT
        'auto_remove' => env('MUMBLE_AUTO_REMOVE', true),

        // Default group for all authenticated users
        'default_group' => env('MUMBLE_DEFAULT_GROUP', 'authenticated'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Sync
    |--------------------------------------------------------------------------
    */

    'permissions' => [
        // Sync corporation membership as groups
        'sync_corporations' => env('MUMBLE_SYNC_CORPS', true),

        // Sync alliance membership as groups
        'sync_alliances' => env('MUMBLE_SYNC_ALLIANCES', true),

        // Sync SeAT squads as groups
        'sync_squads' => env('MUMBLE_SYNC_SQUADS', true),

        // Sync SeAT roles as groups
        'sync_roles' => env('MUMBLE_SYNC_ROLES', true),
    ],
];
