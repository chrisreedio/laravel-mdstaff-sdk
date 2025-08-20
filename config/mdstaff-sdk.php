<?php

// config for ChrisReedIO/MDStaff
return [
    'base_url' => env('MDSTAFF_BASE_URL', 'api.asm-cloud.com'),

    'account_code' => env('MDSTAFF_ACCOUNT_CODE'),

    'auth' => [
        // OAuth is used either way, this just determines which credentials are sent in the OAuth request
        'default' => env('MDSTAFF_AUTH_DEFAULT', 'basic'),

        'basic' => [
            'username' => env('MDSTAFF_BASIC_USERNAME'),
            'password' => env('MDSTAFF_BASIC_PASSWORD'),
        ],
        'oauth' => [
            'client_id' => env('MDSTAFF_OAUTH_CLIENT_ID'),
            'client_secret' => env('MDSTAFF_OAUTH_CLIENT_SECRET'),
        ],
    ],
];
