<?php

return [
    'foundation' => env('STARTER_FOUNDATION', 'Workspace'),
    'purpose' => env('APP_PURPOSE', 'Manage users, roles, and application access.'),
    'logo' => env('APP_LOGO'),
    'primary_color' => env('APP_PRIMARY_COLOR', '#0d6efd'),
    'default_user_password' => env('USER_DEFAULT_PASSWORD'),
    'superadmin' => [
        'userid' => env('SUPERADMIN_USERID', 'superadmin'),
        'name' => env('SUPERADMIN_NAME', 'Platform Superuser'),
        'email' => env('SUPERADMIN_EMAIL'),
        'password' => env('SUPERADMIN_PASSWORD'),
    ],
];
