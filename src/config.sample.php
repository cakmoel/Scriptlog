<?php 
##################### CONFIGURATION FILE #################################

/*************************************************************************

return [

    'db' => [

        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'user' => $_ENV['DB_USER'] ?? '',
        'pass' => $_ENV['DB_PASS'] ?? '',
        'name' => $_ENV['DB_NAME'] ?? '',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'prefix' => $_ENV['DB_PREFIX'] ?? ''
    ],

    'app' => [

        'url'   => $_ENV['APP_URL'] ?? 'https://blogware.site',
        'email' => $_ENV['APP_EMAIL'] ?? '',
        'key'   => $_ENV['APP_KEY'] ?? '',
        'defuse_key' => 'lib/utility/.lts/lts.php'
    ],

    'mail' => [
        'smtp' => [
            'host' => $_ENV['SMTP_HOST'] ?? '',
            'port' => $_ENV['SMTP_PORT'] ?? 587,
            'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
            'username' => $_ENV['SMTP_USER'] ?? '',
            'password' => $_ENV['SMTP_PASS'] ?? '',
        ],
        'from' => [
            'email' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@blogware.site',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Blogware'
        ]
    ],

    'os' => [

        'system_software' => $_ENV['SYSTEM_OS'] ?? 'Linux',
        'distrib_name'    => $_ENV['DISTRIB_NAME'] ?? 'Linux Mint'
    ],

    'api' => [
        'allowed_origins' => $_ENV['CORS_ALLOWED_ORIGINS'] ?? ''
    ],

];


 **************************************************************************/
