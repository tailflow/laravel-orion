<?php

return [
    'namespaces' => [
        'models' => 'App\\Models\\',
        'controllers' => 'App\\Http\\Controllers\\',
    ],
    'auth' => [
        'guard' => 'api',
    ],
    'specs' => [
        'info' => [
            'title' => env('APP_NAME'),
            'description' => null,
            'terms_of_service' => null,
            'contact' => [
                'name' => null,
                'url' => null,
                'email' => null,
            ],
            'license' => [
                'name' => null,
                'url' => null,
            ],
            'version' => '1.0.0',
        ],
        'servers' => [
            ['url' => env('APP_URL').'/api', 'description' => 'Default Environment'],
        ],
        'tags' => [
            'generate' => true,
            'default' => []
        ],
    ],
    'transactions' => [
        'enabled' => false,
    ],
    'search' => [
        'case_sensitive' => false,
        /*
         |--------------------------------------------------------------------------
         | Max Nested Depth
         |--------------------------------------------------------------------------
         |
         | This value is the maximum depth of nested filters.
         | You will most likely need this to be maximum at 1, but
         | you can increase this number, if necessary. Please
         | be aware that the depth generate dynamic rules and can slow
         | your application if someone sends a request with thousands of nested
         | filters.
         |
         */
        'max_nested_depth' => 1,
    ],

    'use_validated' => false,
];
