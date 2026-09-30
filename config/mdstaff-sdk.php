<?php

// config for ChrisReedIO/MDStaff
return [
    /*
     * The ASM Cloud host. The account code is prepended as a subdomain,
     * e.g. "{account_code}.api.asm-cloud.com".
     */
    'base_url' => env('MDSTAFF_BASE_URL', 'api.asm-cloud.com'),

    'account_code' => env('MDSTAFF_ACCOUNT_CODE'),

    /*
     * The facility UID used to scope access tokens when resolving the
     * connector from the container or the MDStaff facade.
     */
    'facility_id' => env('MDSTAFF_FACILITY_ID'),

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

    /*
     * Seconds that identical query responses are cached.
     */
    'query_cache_ttl' => env('MDSTAFF_QUERY_CACHE_TTL', 300),

    'rate_limits' => [
        /*
         * Hard ceiling enforced by the connector. MDStaff limits requests per
         * account, so apps sharing an account should split this between them.
         */
        'requests_per_minute' => env('MDSTAFF_REQUESTS_PER_MINUTE', 10),

        /*
         * Softer budget reserved by background work through RequestBudget,
         * leaving headroom under the hard ceiling for interactive requests.
         */
        'budget_per_minute' => env('MDSTAFF_BUDGET_PER_MINUTE', 8),
    ],
];
