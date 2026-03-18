<?php defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class Bootstrap
 * * Central orchestrator for the MyBlogi framework. This class handles the 
 * sequential loading of configurations, utility functions, core services, 
 * and global security policies.
 * * @category Core
 * @package  Scriptlog\Core
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

    /**
     * Initializes the application environment.
     * * Performs the full bootstrap sequence: configuration, utilities, 
     * service containerization, and security header enforcement.
     *
     * @param string $appRoot The absolute path to the application root directory.
     * @return array<string, mixed> Merged collection of configuration variables and service instances.
     */
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
        // STEP 1: CREATE DATABASE CONNECTION
        $dbc = class_exists('DbFactory') ? DbFactory::connect([
            'mysql:host=' . $core_vars['db_host'] . ';port=' . $core_vars['db_port'] . ';dbname=' . $core_vars['db_name'],
            $core_vars['db_user'],
            $core_vars['db_pwd']
        ]) : "";

        if (isset($core_vars['db_prefix']) && !empty($core_vars['db_prefix']) && method_exists($dbc, 'setTablePrefix')) {
            $dbc->setTablePrefix($core_vars['db_prefix']);
        }

        // STEP 2: Define Routing Rules
        $rules = [
            'home'     => "/",
            'category' => "/category/(?'category'[\w\-]+)",
            'archive'  => "/archive/[0-9]{2}/[0-9]{4}",
            'blog'     => "/blog([^/]*)",
            'page'     => "/page/(?'page'[^/]+)",
            'single'   => "/post/(?'id'\d+)/(?'post'[\w\-]+)",
            'search'   => "(?'search'[\w\-]+)",
            'tag'      => "/tag/(?'tag'[\w\-]+)",
            'privacy'  => "/privacy"
        ];

        // STEP 3: SET REGISTRY
        class_exists('Registry') ? Registry::setAll([
            'dbc' => $dbc,
            'key' => $core_vars['cipher_key'],
            'route' => $rules,
            'uri' => class_exists('RequestPath') ? new RequestPath() : null
        ]) : "";

        // STEP 4: INSTANTIATE SERVICES
        $userDao = class_exists('UserDao') ? new UserDao() : null;
        $userToken = class_exists('UserTokenDao') ? new UserTokenDao() : null;
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
     * Configures HTTP response headers and global security handlers.
     * * Uses utility functions to set CSP, XSS protection, and frame options.
     * Also initializes error handling (Whoops) and HTML purification.
     * * @return void
     */
    private static function applySecurity(): void
    {
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