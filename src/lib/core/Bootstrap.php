<?php
// lib/core/Bootstrap.php

class Bootstrap
{
    private static $config = [];
    private static $services = [];

    public static function initialize(string $appRoot): array
    {
        // 1. Load Configuration and get core variables
        $core_vars = self::loadConfiguration($appRoot);

        // 2. Load Utility Functions (Requires lib/utility-loader.php to exist)
        require __DIR__ . '/../../lib/utility-loader.php';

        // 3. Set Up Services and Registry
        $services = self::initializeServices($core_vars);

        // 4. Apply Security Headers (using functions loaded in step 2)
        self::applySecurity();

        // Merge core variables (db_host, etc.) and instantiated services ($authenticator, etc.)
        return array_merge($core_vars, $services);
    }

    private static function loadConfiguration(string $appRoot): array
    {
        // Configuration loading is safe here because it returns data, not output.
        if (file_exists($appRoot . 'config.php')) {
            self::$config = require $appRoot . 'config.php';
        } else {
            // Install redirect is handled by main.php
            return [];
        }

        // Define core variables that were previously pulled directly from the config array
        $db_host = self::$config['db']['host'] ?? "";
        $db_user = self::$config['db']['user'] ?? "";
        $db_pwd  = self::$config['db']['pass'] ?? "";
        $db_name = self::$config['db']['name'] ?? "";
        $db_port = self::$config['db']['port'] ?? "";
        $db_prefix = self::$config['db']['prefix'] ?? "";

        $app_email = self::$config['app']['email'] ?? "";
        $app_url   = self::$config['app']['url'] ?? "";
        $app_key   = self::$config['app']['key'] ?? "";

        // The cipher_key is instantiated via a utility function
        $cipher_key = class_exists('ScriptlogCryptonize') ? ScriptlogCryptonize::scriptlogCipherKey() : "";

        // Returns variables to be extracted into the global scope of main.php
        return compact('db_host', 'db_user', 'db_pwd', 'db_name', 'db_port', 'db_prefix', 'app_email', 'app_url', 'app_key', 'cipher_key');
    }

    private static function initializeServices(array $core_vars): array
    {
        // STEP 1: CREATE DATABASE CONNECTION (Must come first)
        $dbc = class_exists('DbFactory') ? DbFactory::connect([
            'mysql:host=' . $core_vars['db_host'] . ';port=' . $core_vars['db_port'] . ';dbname=' . $core_vars['db_name'],
            $core_vars['db_user'],
            $core_vars['db_pwd']
        ]) : "";

        // Set table prefix if configured
        if (isset($core_vars['db_prefix']) && !empty($core_vars['db_prefix']) && method_exists($dbc, 'setTablePrefix')) {
            $dbc->setTablePrefix($core_vars['db_prefix']);
        }

        // STEP 2: Define Rules (Needed for Registry)
        $rules = [
            'home'     => "/",
            'category' => "/category/(?'category'[\w\-]+)",
            'archive'  => "/archive/[0-9]{2}/[0-9]{4}",
            'blog'     => "/blog([^/]*)",
            'page'     => "/page/(?'page'[^/]+)",
            'single'   => "/post/(?'id'\d+)/(?'post'[\w\-]+)",
            'search'   => "(?'search'[\w\-]+)",
            'tag'      => "/tag/(?'tag'[\w\-]+)"
        ];

        // STEP 3: SET REGISTRY (Must come BEFORE instantiating Dao-dependent services)
        class_exists('Registry') ? Registry::setAll([
            'dbc' => $dbc, // NOW THE DBC IS AVAILABLE
            'key' => $core_vars['cipher_key'],
            'route' => $rules,
            'uri' => class_exists('RequestPath') ? new RequestPath() : null
        ]) : "";
        // STEP 4: INSTANTIATE SERVICES
        $userDao = class_exists('UserDao') ? new UserDao() : null; // Safe now
        $userToken = class_exists('UserTokenDao') ? new UserTokenDao() : null; // Safe now
        $validator = class_exists('FormValidator') ? new FormValidator() : null;

        $sessionMaker = class_exists('SessionMaker') ? new SessionMaker(set_session_cookies_key($core_vars['app_email'], $core_vars['app_key'])) : null;

        self::$services = [
            'sessionMaker' => $sessionMaker,
            'searchPost' => class_exists('SearchFinder') ? new SearchFinder() : null,
            'sanitizer' => class_exists('Sanitize') ? new Sanitize() : null,
            'userDao' => $userDao,
            'userToken' => $userToken,
            'validator' => $validator,
            'authenticator' => class_exists('Authentication') ? new Authentication($userDao, $userToken, $validator) : null,
            'ubench' => class_exists('Ubench') ? new Ubench() : null,
            'dispatcher' => class_exists('Dispatcher') ? new Dispatcher() : null,
        ];

        return self::$services;
    }

    /**
     * Calls global security functions.
     */
    private static function applySecurity(): void
    {
        // These global functions must be loaded via utility-loader.php
        if (!headers_sent()) {
            x_frame_option();
            x_content_type_options();
            x_xss_protection();
            strict_transport_security();
            content_security_policy(self::$config['app']['url'] ?? '');
            remove_x_powered_by();
        }

        call_htmlpurifier();
        get_server_load();
        whoops_error();
    }
}
