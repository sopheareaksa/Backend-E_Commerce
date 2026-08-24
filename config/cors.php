<?php

return [
    'paths' => ['*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://frontend-e-commerce-rose-phi.vercel.app',
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        '*',
    ],

    'allowed_origins_patterns' => [
        '#^https://.*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],
    'exposed_headers' => ['*'],
    'max_age' => 86400,
    'supports_credentials' => false,
];
