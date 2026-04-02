<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Agrega AQUÍ la URL de tu frontend de Render (sin la barra / al final)
    'allowed_origins' => [
        'https://gasstationfront.onrender.com',
        'http://localhost:5173', // Para que sigas pudiendo probar en local
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // Importante para Sanctum/Tokens
];