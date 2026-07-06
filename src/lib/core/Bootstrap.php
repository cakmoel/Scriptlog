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

    private static $allowedExportVars = [
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
        'userToken',
        'themeRenderer',
        'mediaDao',
        'downloadService',
        'downloadController',
        'frontService',
        'postDao',
        'pageDao',
        'topicDao'
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
        try {
            self::applySecurity();
        } catch (\Throwable $e) {
            error_log('Security header application failed: ' . $e->getMessage());
        }

        $all_vars = array_merge($core_vars, $services);

        // Only return what is explicitly allowed
        return new AppContext(array_intersect_key($all_vars, array_flip(self::$allowedExportVars)));
    }

    /**
     * Loads the configuration file and extracts required environment variables.
     *
     * @param string $appRoot Path to search for config.php.
     * @return array<string, string> Array containing db_host, app_url, cipher_key, etc.
     */
    private static function loadConfiguration(string $appRoot): array
    {
        if (!file_exists($appRoot . 'config.php')) {
            return [];
        }

        self::$config = require $appRoot . 'config.php';

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
        $dbc = self::createDatabaseConnection($core_vars);

        $rules = self::defineRoutingRules();

        self::initializeRegistry($dbc, $core_vars, $rules);

        list($userDao, $userToken, $validator, $sanitizer, $configDao, $configService) = self::createBaseDaos($dbc);

        $sessionMaker = self::createSession($core_vars);

        $authenticator = self::createAuthenticator($dbc, $userDao, $userToken, $validator);

        $themeRenderer = self::createThemeRenderer($dbc);

        if ($themeRenderer && class_exists('HandleRequest')) {
            HandleRequest::setThemeRenderer($themeRenderer);
        }

        $frontService = self::createFrontService();

        list($mediaDao, $downloadService, $downloadController) = self::createDownloadChain($dbc);

        list($postDao, $pageDao, $topicDao) = self::createContentDaos($dbc);

        self::storeInRegistry($mediaDao, $downloadService, $downloadController, $postDao, $pageDao, $topicDao);

        $dispatcher = self::createDispatcher($dbc, $themeRenderer);

        self::$services = self::buildServiceMap($sessionMaker, $sanitizer, $userDao, $userToken, $validator, $configDao, $configService, $authenticator, $dispatcher, $themeRenderer, $mediaDao, $downloadService, $downloadController, $frontService, $postDao, $pageDao, $topicDao);

        self::buildHandlerRegistry($themeRenderer);

        self::initializeI18n();

        return self::$services;
    }

    private static function createDatabaseConnection(array $core_vars)
    {
        $dbc = "";

        if (
            class_exists('DbFactory') &&
            !empty($core_vars['db_host']) &&
            !empty($core_vars['db_user']) &&
            !empty($core_vars['db_name'])
        ) {
            try {
                $dbc = DbFactory::connect([
                    'mysql:host=' . $core_vars['db_host'] . ';port=' . $core_vars['db_port'] . ';dbname=' . $core_vars['db_name'] . ';charset=utf8mb4',
                    $core_vars['db_user'],
                    $core_vars['db_pwd']
                ]);
            } catch (Exception $e) {
                $dbc = "";
            }
        }

        if (!empty($dbc) && isset($core_vars['db_prefix']) && !empty($core_vars['db_prefix']) && method_exists($dbc, 'setTablePrefix')) {
            $dbc->setTablePrefix($core_vars['db_prefix']);
        }

        return $dbc;
    }

    private static function defineRoutingRules(): array
    {
        return [
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
    }

    private static function initializeRegistry($dbc, array $core_vars, array $rules): void
    {
        class_exists('Registry') ? Registry::setAll([
            'dbc' => $dbc,
            'key' => $core_vars['cipher_key'] ?? '',
            'route' => $rules,
            'uri' => class_exists('RequestPath') ? new RequestPath() : null
        ]) : "";
    }

    private static function createBaseDaos($dbc): array
    {
        $userDao = null;
        $userToken = null;
        $validator = null;
        $sanitizer = null;
        $configDao = null;
        $configService = null;

        if (!empty($dbc) && $dbc !== "") {
            $userDao = class_exists('UserDao') ? new UserDao() : null;
            $userToken = class_exists('UserTokenDao') ? new UserTokenDao() : null;
            $validator = class_exists('FormValidator') ? new FormValidator() : null;
            $sanitizer = class_exists('Sanitize') ? new Sanitize() : null;
            $configDao = class_exists('ConfigurationDao') ? new ConfigurationDao() : null;
            $configService = ($configDao && $validator && $sanitizer) ? new ConfigurationService($configDao, $validator, $sanitizer) : null;
        }

        return [$userDao, $userToken, $validator, $sanitizer, $configDao, $configService];
    }

    private static function createSession(array $core_vars)
    {
        $sessionMaker = null;
        if (class_exists('SessionMaker')) {
            try {
                if (!headers_sent() || PHP_SAPI === 'cli') {
                    $sessionMaker = new SessionMaker(set_session_cookies_key($core_vars['app_email'] ?? '', $core_vars['app_key'] ?? ''));
                }
            } catch (Exception $e) {
                // Session creation failed silently - acceptable during early bootstrap
            }
        }

        if ($sessionMaker) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            session_save_path(resolve_session_save_path());
            session_set_save_handler($sessionMaker, true);
            register_shutdown_function('session_write_close');
            if (function_exists('start_session_on_site')) {
                start_session_on_site($sessionMaker);
            }
        }

        return $sessionMaker;
    }

    private static function createAuthenticator($dbc, $userDao, $userToken, $validator)
    {
        if (!class_exists('Authentication') || empty($dbc) || $dbc === "") {
            return null;
        }

        try {
            return new Authentication($userDao, $userToken, $validator);
        } catch (Exception $e) {
            return null;
        }
    }

    private static function createThemeRenderer($dbc)
    {
        if (!class_exists('ThemeRenderer') || !(isset($dbc) && $dbc instanceof \PDO)) {
            return null;
        }

        try {
            return ThemeRenderer::fromGlobalContext();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function createFrontService()
    {
        if (!class_exists('FrontService')) {
            return null;
        }

        try {
            $frontService = new FrontService();
            if (class_exists('FrontHelper')) {
                FrontHelper::setFrontService($frontService);
            }
            return $frontService;
        } catch (Exception $e) {
            return null;
        }
    }

    private static function createDownloadChain($dbc): array
    {
        $mediaDao = null;
        $downloadService = null;
        $downloadController = null;

        if (!empty($dbc) && $dbc !== "") {
            $mediaDao = class_exists('MediaDao') ? new MediaDao() : null;
            $downloadModel = class_exists('DownloadModel') ? new DownloadModel() : null;
            if ($downloadModel && $mediaDao) {
                $downloadService = new DownloadService($downloadModel, $mediaDao);
            }
            if ($downloadService) {
                $downloadController = new DownloadController($downloadService);
            }
        }

        return [$mediaDao, $downloadService, $downloadController];
    }

    private static function createContentDaos($dbc): array
    {
        $postDao = null;
        $pageDao = null;
        $topicDao = null;

        if (!empty($dbc) && $dbc !== "") {
            $postDao = class_exists('PostDao') ? new PostDao() : null;
            $pageDao = class_exists('PageDao') ? new PageDao() : null;
            $topicDao = class_exists('TopicDao') ? new TopicDao() : null;
        }

        return [$postDao, $pageDao, $topicDao];
    }

    private static function storeInRegistry($mediaDao, $downloadService, $downloadController, $postDao, $pageDao, $topicDao): void
    {
        if (!class_exists('Registry')) {
            return;
        }

        Registry::set('mediaDao', $mediaDao);
        Registry::set('downloadService', $downloadService);
        Registry::set('downloadController', $downloadController);
        Registry::set('postDao', $postDao);
        Registry::set('pageDao', $pageDao);
        Registry::set('topicDao', $topicDao);
    }

    private static function createDispatcher($dbc, $themeRenderer)
    {
        if (!class_exists('Dispatcher') || empty($dbc) || $dbc === "") {
            return null;
        }

        try {
            return new Dispatcher($themeRenderer);
        } catch (Exception $e) {
            return null;
        }
    }

    /** @SuppressWarnings(PHPMD.ExcessiveParameterList) */
    private static function buildServiceMap($sessionMaker, $sanitizer, $userDao, $userToken, $validator, $configDao, $configService, $authenticator, $dispatcher, $themeRenderer, $mediaDao, $downloadService, $downloadController, $frontService, $postDao, $pageDao, $topicDao): array
    {
        return [
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
            'themeRenderer' => $themeRenderer,
            'mediaDao' => $mediaDao,
            'downloadService' => $downloadService,
            'downloadController' => $downloadController,
            'frontService' => $frontService,
            'postDao' => $postDao,
            'pageDao' => $pageDao,
            'topicDao' => $topicDao,
        ];
    }

    private static function buildHandlerRegistry($themeRenderer): void
    {
        if (!$themeRenderer) {
            return;
        }

        $handlerRegistry = new HandlerRegistry();

        $downloadController = self::$services['downloadController'] ?? null;

        $handlerRegistry->register('p', new PostHandler($themeRenderer));
        $handlerRegistry->register('pg', new PageHandler($themeRenderer));
        $handlerRegistry->register('cat', new CategoryHandler($themeRenderer));
        $handlerRegistry->register('tag', new TagHandler($themeRenderer));
        $handlerRegistry->register('a', new ArchiveHandler($themeRenderer));
        $handlerRegistry->register('blog', new BlogHandler($themeRenderer));
        $handlerRegistry->register('privacy', new PrivacyHandler($themeRenderer));

        if ($downloadController) {
            $handlerRegistry->register('download', new DownloadHandler($themeRenderer, $downloadController));
        }

        class_exists('Registry') ? Registry::set('handlerRegistry', $handlerRegistry) : null;
    }

    private static function initializeI18n(): void
    {
        if (!class_exists('I18nManager')) {
            return;
        }

        $i18n = I18nManager::getInstance();
        $i18n->initialize();
        self::$services['i18n'] = $i18n;
    }

    /**
     * Configures HTTP response headers and global security handlers.
     * * Uses utility functions to set CSP, XSS protection, and frame options.
     * Also initializes error handling (Whoops) and HTML purification.
     * * @return void
     */
    private static function applySecurity(): void
    {
        self::defineCspNonce();

        if (!headers_sent() && PHP_SAPI !== 'cli') {
            self::sendSecurityHeaders();
            self::sendContentSecurityPolicy();
        }

        self::initializePostSecurity();
    }

    private static function defineCspNonce(): void
    {
        if (!defined('CSP_NONCE')) {
            define('CSP_NONCE', base64_encode(random_bytes(20)));
        }
    }

    private static function sendSecurityHeaders(): void
    {
        $headerFunctions = [
            'x_frame_option',
            'x_content_type_options',
            'x_xss_protection',
            'strict_transport_security',
            'referrer_policy',
            'permissions_policy',
            'remove_x_powered_by'
        ];
        foreach ($headerFunctions as $func) {
            if (function_exists($func)) {
                try {
                    $func();
                } catch (\Throwable $e) {
                    error_log("Security header [$func] failed: " . $e->getMessage());
                }
            }
        }
    }

    private static function sendContentSecurityPolicy(): void
    {
        if (function_exists('content_security_policy')) {
            try {
                content_security_policy(self::$config['app']['url'] ?? '');
            } catch (\Throwable $e) {
                error_log("Security header [content_security_policy] failed: " . $e->getMessage());
            }
        }
    }

    private static function initializePostSecurity(): void
    {
        if (function_exists('call_htmlpurifier')) {
            call_htmlpurifier();
        }
        if (function_exists('get_server_load')) {
            try {
                get_server_load();
            } catch (\Throwable $e) {
                // Server load check failed silently
            }
        }
        if (function_exists('whoops_error')) {
            try {
                whoops_error();
            } catch (Exception $e) {
                // Whoops error handler initialization failed silently
            }
        }
    }
}
