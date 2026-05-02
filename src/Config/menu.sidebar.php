<?php

return [
    // Admin menu (visible only to superusers)
    [
        'name'          => 'Mumble Admin',
        'icon'          => 'fas fa-headset',
        'route_segment' => 'mumble-admin',
        'permission'    => 'global.superuser',
        'entries'       => [
            [
                'name'  => 'Dashboard',
                'icon'  => 'fas fa-tachometer-alt',
                'route' => 'mumble::admin.index',
            ],
            [
                'name'  => 'Settings',
                'icon'  => 'fas fa-cog',
                'route' => 'mumble::admin.settings',
            ],
            [
                'name'  => 'Group Mappings',
                'icon'  => 'fas fa-layer-group',
                'route' => 'mumble::admin.groups',
            ],
            [
                'name'  => 'Users',
                'icon'  => 'fas fa-users',
                'route' => 'mumble::admin.users',
            ],
            [
                'name'  => 'Temporary Links',
                'icon'  => 'fas fa-link',
                'route' => 'mumble::admin.links',
            ],
            [
                'name'  => 'Logs',
                'icon'  => 'fas fa-history',
                'route' => 'mumble::admin.logs',
            ],
        ],
    ],

    // User-facing menu (visible to all authenticated users)
    [
        'name'          => 'Mumble',
        'icon'          => 'fas fa-headset',
        'route_segment' => 'mumble',
        'entries'       => [
            [
                'name'  => 'My Account',
                'icon'  => 'fas fa-user',
                'route' => 'mumble::user.profile',
            ],
        ],
    ],
];
