<?php
/**
 * Concrete configuration used ONLY by the Playwright e2e CI workflow.
 *
 * The tracker copies this file to config.php on CI runners (config.php is
 * gitignored). Values match the seeded e2e database (e2e/blogware_e2e.sql), the
 * MariaDB service container, and the app served at http://127.0.0.1:8099.
 *
 * It is NOT read by the application locally (the real config.php is used)
 * and it is not part of the shipped application. See e2e/router.php.
 *
 * @category E2E Fixture
 * @package  Scriptlog
 * @license  MIT
 */

return [

    'db' => [

        'host' => '127.0.0.1',
        'user' => 'blogwareuser',
        'pass' => 'userblogware',
        'name' => 'blogware_e2e',
        'port' => '3306',
        'prefix' => ''
    ],

    'app' => [

        'url'   => 'http://127.0.0.1:8099',
        'email' => 'admin@blogware.site',
        'key'   => 'GVXUD7-72HUXD-2TFCDT-8DDC2A',
        'defuse_key' => 'lib/utility/.lts/lts.php'
    ],

    'mail' => [
        'smtp' => [
            'host' => '',
            'port' => 587,
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
        ],
        'from' => [
            'email' => 'noreply@blogware.site',
            'name' => 'Blogware'
        ]
    ],

    'os' => [

        'system_software' => 'Linux',
        'distrib_name'    => 'Ubuntu'
    ],

    'api' => [
        'allowed_origins' => ''
    ],

];