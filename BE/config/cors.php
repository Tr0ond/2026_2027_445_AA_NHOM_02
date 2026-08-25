<?php

return [
    'paths' => ['api/*', 'broadcasting/auth'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FE_URL', 'http://localhost:5173'), 'http://localhost:5173', 'http://127.0.0.1:5173'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
