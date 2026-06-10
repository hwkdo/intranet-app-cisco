<?php

// config for Hwkdo/IntranetAppCisco
return [
'roles' => [
        'admin' => [
            'name' => 'App-Cisco-Admin',
            'permissions' => [
                'see-app-cisco',
                'manage-app-cisco',
            ]
        ],
        'user' => [
            'name' => 'App-Cisco-Benutzer',
            'permissions' => [
                'see-app-cisco',                
            ]
        ],
]
];
