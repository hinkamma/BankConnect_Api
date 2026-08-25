<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://bank-connect-front.vercel.app',
        'http://localhost:4200',
        'http://127.0.0.1:4200',
        env('FRONTEND_URL', ''), // Récupère aussi l'URL depuis le .env si elle existe
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
