<?php

return [
    'app' => [
        'name' => $_ENV['APP_COMPANY_NAME'] ?? 'Beetle Analytics',
        'tagline' => $_ENV['APP_TAGLINE'] ?? 'Restaurant Performance Dashboard',
        'env' => $_ENV['APP_ENV'] ?? 'local',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
    ],
    'db' => [
        'connection' => $_ENV['DB_CONNECTION'] ?? 'mysql',
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? 'beetle_analytics',
        'username' => $_ENV['DB_USERNAME'] ?? 'beetle_user',
        'password' => $_ENV['DB_PASSWORD'] ?? 'beetle_pass',
    ],
];
