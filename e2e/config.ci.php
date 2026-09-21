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

// CI-only fixture config. Every value prefers its E2E_* env override so CI can
// inject GitHub Secrets; the literals below are ephemeral local-test defaults
// for the throwaway blogware_e2e service container, never production secrets.
return [

    'db' => [

        'host' => $_ENV['E2E_DB_HOST'] ?? getenv('E2E_DB_HOST') ?: '127.0.0.1',
        'user' => $_ENV['E2E_DB_USER'] ?? getenv('E2E_DB_USER') ?: 'blogwareuser',
        'pass' => $_ENV['E2E_DB_PASS'] ?? getenv('E2E_DB_PASS') ?: 'userblogware',
        'name' => $_ENV['E2E_DB_NAME'] ?? getenv('E2E_DB_NAME') ?: 'blogware_e2e',
        'port' => $_ENV['E2E_DB_PORT'] ?? getenv('E2E_DB_PORT') ?: '3306',
        'prefix' => $_ENV['E2E_DB_PREFIX'] ?? getenv('E2E_DB_PREFIX') ?: ''
    ],

    'app' => [

        'url'   => $_ENV['PLAYWRIGHT_BASE_URL'] ?? getenv('PLAYWRIGHT_BASE_URL') ?: 'http://127.0.0.1:8099',
        'email' => 'admin@blogware.site',
        'key'   => $_ENV['E2E_APP_KEY'] ?? getenv('E2E_APP_KEY') ?: 'F5R5TE-WL7VSG-KKAZRH-377C04',
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