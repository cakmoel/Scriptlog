<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class Bootstrap
 *
 * Central orchestrator for this blog. This class handles 
 * the sequential loading of configurations, utility functions, core services,
 * and global security policies.
 *
 * @category Core
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    1.0.0
 */
class Bootstrap
{
    /**
     * @var array<string, mixed> Holds the raw configuration array from config.php.
     */
    private static $config = [];

    /**
     * @var array<string, object|null> Container for instantiated application services.
     */
    private static $services = [];

    private static $allowed_exported_vars = [
        'db_host',
        'db_user',
        'db_pwd',
        'db_name',
        'db_port',
        'db_prefix',
        'app_email',
        'app_url',
        'app_key',
        'cipher_key',
        'sessionMaker',
        'searchPost',
        'authenticator',
        'ubench',
        'sanitizer',
        'searchPost',
        'validator',
        'configDao',
        'configService',
        'dispatcher',
        'i18n',
        'userDao',
        'userToken'
    ];

    /**
     * Initializes the application environment.
     * * Performs the full bootstrap sequence: configuration, utilities,
     * service containerization, and security header enforcement.
     *
     * @param string $appRoot The absolute path to the application root directory.
     * @return AppContext Merged collection of configuration variables and service instances.
     */
    public static function initialize(string $appRoot): AppContext
    {
        // 1. Load Configuration and get core variables
        $core_vars = self::loadConfiguration($appRoot);

        // 2. Load Utility Functions (Requires lib/utility-loader.php to exist)
        require_once __DIR__ . '/../../lib/utility-loader.php';

        // 3. Set Up Services and Registry
        $services = self::initializeServices($core_vars);

        // 4. Apply Security Headers (using functions loaded in step 2)
        self::applySecurity();

        $all_vars = array_merge($core_vars, $services);

        // Only return what is explicitly allowed
        return new AppContext(array_intersect_key($all_vars, array_flip(self::$allowed_exported_vars)));
    }

    /**
     * Loads the configuration file and extracts required environment variables.
     *
     * @param string $appRoot Path to search for config.php.
     * @return array<string, string> Array containing db_host, app_url, cipher_key, etc.
     */
    private static function loadConfiguration(string $appRoot): array
    {
        if (file_exists($appRoot . 'config.php')) {
            self::$config = require $appRoot . 'config.php';
        } else {
            return [];
        }

        $db_host = self::$config['db']['host'] ?? "";
        $db_user = self::$config['db']['user'] ?? "";
        $db_pwd  = self::$config['db']['pass'] ?? "";
        $db_name = self::$config['db']['name'] ?? "";
        $db_port = self::$config['db']['port'] ?? "";
        $db_prefix = self::$config['db']['prefix'] ?? "";

        $app_email = self::$config['app']['email'] ?? "";
        $app_url   = self::$config['app']['url'] ?? "";
        $app_key   = self::$config['app']['key'] ?? "";

        if (empty($app_key)) {
            throw new Exception("Security Risk: APP_KEY is missing from environment.");
        }
        $cipher_key = class_exists('ScriptlogCryptonize') ? ScriptlogCryptonize::scriptlogCipherKey() : "";

        return compact('db_host', 'db_user', 'db_pwd', 'db_name', 'db_port', 'db_prefix', 'app_email', 'app_url', 'app_key', 'cipher_key');
    }

    /**
     * Orchestrates service instantiation and global registry setup.
     * * Note: Registry must be populated before DAOs are instantiated to ensure
     * data access objects have access to the active database connection.
     *
     * @param array<string, string> $core_vars Extracted configuration variables.
     * @return array<string, object|null> Collection of ready-to-use services.
     * @uses DbFactory::connect()
     * @uses Registry::setAll()
     */
    private static function initializeServices(array $core_vars): array
    {
        // STEP 1: CREATE DATABASE CONNECTION (only if all required keys exist)
        $dbc = "";
        
        if (class_exists('DbFactory') && 
            !empty($core_vars['db_host']) && 
            !empty($core_vars['db_user']) && 
            !empty($core_vars['db_name'])) {
            try {
                $dbc = DbFactory::connect([
                    'mysql:host=' . $core_vars['db_host'] . ';port=' . $core_vars['db_port'] . ';dbname=' . $core_vars['db_name'] . ';charset=utf8mb4',
                    $core_vars['db_user'],
                    $core_vars['db_pwd']
                ]);
            } catch (Exception $e) {
                // Database connection failed - continue without db connection
                $dbc = "";
            }
        }

        if (!empty($dbc) && isset($core_vars['db_prefix']) && !empty($core_vars['db_prefix']) && method_exists($dbc, 'setTablePrefix')) {
            $dbc->setTablePrefix($core_vars['db_prefix']);
        }

        // STEP 2: Define Routing Rules
        $rules = [
            'home'     => "/",
            'category' => "/category/(?'category'[\w\-]+)",
            'archive'  => "/archive/[0-9]{2}/[0-9]{4}",
            'archives' => "/archives",
            'blog'     => "/blog([^/]*)",
            'page'     => "/page/(?'page'[^/]+)",
            'single'   => "/post/(?'id'\d+)/(?'post'[\w\-]+)",
            'search'   => "(?'search'[\w\-]+)",
            'tag'      => "/tag/(?'tag'[\w\- ]+)",
            'privacy'  => "/privacy",
            'download' => "/download/(?'identifier'[a-f0-9\-]+)",
            'download_file' => "/download/(?'identifier'[a-f0-9\-]+)/file"
        ];

        // STEP 3: SET REGISTRY
        class_exists('Registry') ? Registry::setAll([
            'dbc' => $dbc,
            'key' => $core_vars['cipher_key'] ?? '',
            'route' => $rules,
            'uri' => class_exists('RequestPath') ? new RequestPath() : null
        ]) : "";

        // STEP 4: INSTANTIATE SERVICES
        $userDao = null;
        $userToken = null;
        $validator = null;
        $sanitizer = null;
        
        // Only instantiate DAOs if we have a valid database connection
        if (!empty($dbc) && $dbc !== "") {
            $userDao = class_exists('UserDao') ? new UserDao() : null;
            $userToken = class_exists('UserTokenDao') ? new UserTokenDao() : null;
            $validator = class_exists('FormValidator') ? new FormValidator() : null;
            $sanitizer = class_exists('Sanitize') ? new Sanitize() : null;

            $configDao = class_exists('ConfigurationDao') ? new ConfigurationDao() : null;
            $configService = ($configDao && $validator && $sanitizer) ? new ConfigurationService($configDao, $validator, $sanitizer) : null;
        } else {
            $configDao = null;
            $configService = null;
        }

        $sessionMaker = null;
        if (class_exists('SessionMaker')) {
            try {
                if (!headers_sent() || PHP_SAPI === 'cli') {
                    $sessionMaker = new SessionMaker(set_session_cookies_key($core_vars['app_email'] ?? '', $core_vars['app_key'] ?? ''));
                }
            } catch (Exception $e) {
                // Session creation failed - continue without session
            }
        }

        $authenticator = null;
        if (class_exists('Authentication') && !empty($dbc) && $dbc !== "") {
            try {
                $authenticator = new Authentication($userDao, $userToken, $validator);
            } catch (Exception $e) {
                // Authentication creation failed
            }
        }

        $dispatcher = null;
        if (class_exists('Dispatcher') && !empty($dbc) && $dbc !== "") {
            try {
                $dispatcher = new Dispatcher();
            } catch (Exception $e) {
                // Dispatcher creation failed
            }
        }

        self::$services = [
            'sessionMaker' => $sessionMaker,
            'searchPost' => class_exists('SearchFinder') ? new SearchFinder() : null,
            'sanitizer' => $sanitizer,
            'userDao' => $userDao,
            'userToken' => $userToken,
            'validator' => $validator,
            'configDao' => $configDao,
            'configService' => $configService,
            'authenticator' => $authenticator,
            'ubench' => class_exists('Ubench') ? new Ubench() : null,
            'dispatcher' => $dispatcher,
        ];

        // STEP 5: Initialize i18n if available
        if (class_exists('I18nManager')) {
            $i18n = I18nManager::getInstance();
            $i18n->initialize();
            self::$services['i18n'] = $i18n;
        }

        return self::$services;
    }

    /**
     * Configures HTTP response headers and global security handlers.
     * * Uses utility functions to set CSP, XSS protection, and frame options.
     * Also initializes error handling (Whoops) and HTML purification.
     * * @return void
     */
    private static function applySecurity(): void
    {
        if (!headers_sent() && PHP_SAPI !== 'cli') {
            x_frame_option();
            x_content_type_options();
            x_xss_protection();
            strict_transport_security();
            content_security_policy(self::$config['app']['url'] ?? '');
            remove_x_powered_by();
        }

        if (function_exists('call_htmlpurifier')) {
            call_htmlpurifier();
        }
        if (function_exists('get_server_load')) {
            try {
                get_server_load();
            } catch (Exception $e) {
                // Ignore server load check errors
            }
        }
        if (function_exists('whoops_error')) {
            try {
                whoops_error();
            } catch (Exception $e) {
                // Ignore whoops errors
            }
        }
    }
}
