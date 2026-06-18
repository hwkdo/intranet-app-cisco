<?php

// config for Hwkdo/IntranetAppCisco
return [
    'user_model' => env('INTRANET_APP_CISCO_USER_MODEL', \App\Models\User::class),

    'roles' => [
        'admin' => [
            'name' => 'App-Cisco-Admin',
            'permissions' => [
                'see-app-cisco',
                'manage-app-cisco',
            ],
        ],
        'user' => [
            'name' => 'App-Cisco-Benutzer',
            'permissions' => [
                'see-app-cisco',
            ],
        ],
    ],
];
