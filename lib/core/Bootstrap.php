<?php

namespace Scriptlog\Core;

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

use Scriptlog\Controller\DownloadController;
use Scriptlog\Dao\ConfigurationDao;
use Scriptlog\Dao\MediaDao;
use Scriptlog\Dao\PageDao;
use Scriptlog\Dao\PostDao;
use Scriptlog\Dao\TopicDao;
use Scriptlog\Dao\UserDao;
use Scriptlog\Dao\UserTokenDao;
use Scriptlog\Handler\Admin\Comment\DeleteCommentCmd;
use Scriptlog\Handler\Admin\Comment\EditCommentCmd;
use Scriptlog\Handler\Admin\Comment\ListCommentsCmd;
use Scriptlog\Handler\Admin\Topic\DeleteTopicCmd;
use Scriptlog\Handler\Admin\Topic\EditTopicCmd;
use Scriptlog\Handler\Admin\Topic\ListTopicsCmd;
use Scriptlog\Handler\Admin\Topic\NewTopicCmd;
use Scriptlog\Handler\Admin\Post\DeletePostCmd;
use Scriptlog\Handler\Admin\Post\EditPostCmd;
use Scriptlog\Handler\Admin\Post\ListPostsCmd;
use Scriptlog\Handler\Admin\Post\NewPostCmd;
use Scriptlog\Handler\Admin\Page\DeletePageCmd;
use Scriptlog\Handler\Admin\Page\EditPageCmd;
use Scriptlog\Handler\Admin\Page\ListPagesCmd;
use Scriptlog\Handler\Admin\Page\NewPageCmd;
use Scriptlog\Handler\Admin\User\DeleteUserCmd;
use Scriptlog\Handler\Admin\User\EditUserCmd;
use Scriptlog\Handler\Admin\User\ListUsersCmd;
use Scriptlog\Handler\Admin\User\NewUserCmd;
use Scriptlog\Handler\Admin\Media\DeleteMediaCmd;
use Scriptlog\Handler\Admin\Media\EditMediaCmd;
use Scriptlog\Handler\Admin\Media\ListMediaCmd;
use Scriptlog\Handler\Admin\Media\NewMediaCmd;
use Scriptlog\Handler\Admin\Plugin\ActivatePluginCmd;
use Scriptlog\Handler\Admin\Plugin\DeactivatePluginCmd;
use Scriptlog\Handler\Admin\Plugin\DeletePluginCmd;
use Scriptlog\Handler\Admin\Plugin\InstallPluginCmd;
use Scriptlog\Handler\Admin\Plugin\ListPluginsCmd;
use Scriptlog\Handler\Admin\Theme\ActivateThemeCmd;
use Scriptlog\Handler\Admin\Theme\DeactivateThemeCmd;
use Scriptlog\Handler\Admin\Theme\DeleteThemeCmd;
use Scriptlog\Handler\Admin\Theme\EditThemeCmd;
use Scriptlog\Handler\Admin\Theme\InstallThemeCmd;
use Scriptlog\Handler\Admin\Theme\ListThemesCmd;
use Scriptlog\Handler\Admin\Theme\NewThemeCmd;
use Scriptlog\Handler\AdminActionRegistry;
use Scriptlog\Handler\ArchiveHandler;
use Scriptlog\Handler\BlogHandler;
use Scriptlog\Handler\CategoryHandler;
use Scriptlog\Handler\DownloadHandler;
use Scriptlog\Handler\HandlerRegistry;
use Scriptlog\Handler\PageHandler;
use Scriptlog\Handler\PostHandler;
use Scriptlog\Handler\PrivacyHandler;
use Scriptlog\Handler\TagHandler;
use Scriptlog\Model\DownloadModel;
use Scriptlog\Service\ConfigurationService;
use Scriptlog\Service\DownloadService;
use Scriptlog\Service\FrontService;

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
        'topicDao',
        'adminActionRegistry'
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
            throw new \Exception("Security Risk: APP_KEY is missing from environment.");
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

        list($mediaDao, $downloadService, $downloadController) = self::createDownloadChain($dbc);

        list($postDao, $pageDao, $topicDao) = self::createContentDaos($dbc);

        self::storeInRegistry($mediaDao, $downloadService, $downloadController, $postDao, $pageDao, $topicDao);

        // FrontService resolves its DAOs from the Registry at construction time,
        // so it must be built after the content DAOs are registered above.
        $frontService = self::createFrontService();

        // Register the shared FrontService in the global Registry so that
        // HandleRequest::handleFrontHelper() and front_service() can resolve it
        // at request time (query-string delivery, handlers, theme helpers).
        class_exists('Registry') ? Registry::set('frontService', $frontService) : null;

        $dispatcher = self::createDispatcher($dbc, $themeRenderer);

        self::$services = self::buildServiceMap($sessionMaker, $sanitizer, $userDao, $userToken, $validator, $configDao, $configService, $authenticator, $dispatcher, $themeRenderer, $mediaDao, $downloadService, $downloadController, $frontService, $postDao, $pageDao, $topicDao);

        self::buildHandlerRegistry($themeRenderer);

        self::buildAdminActionRegistry();

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
            } catch (\Exception $e) {
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
            'tag'      => "/tag/(?'tag'[\w\- ]+)",
            'search'   => "/search",
            'privacy'  => "/privacy",
            'locale'   => "/locale",
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
            } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Throwable $e) {
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
        } catch (\Exception $e) {
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

    /**
     * Build the admin action command registry and register known commands.
     *
     * @return void
     */
    private static function buildAdminActionRegistry(): void
    {
        if (!class_exists('AdminActionRegistry')) {
            return;
        }

        $registry = new AdminActionRegistry();

        // Comment commands
        if (class_exists('EditCommentCmd')) {
            $registry->register(ActionConst::EDITCOMMENT, new EditCommentCmd());
        }
        if (class_exists('DeleteCommentCmd')) {
            $registry->register(ActionConst::DELETECOMMENT, new DeleteCommentCmd());
        }
        if (class_exists('ListCommentsCmd')) {
            $registry->register('default', new ListCommentsCmd());
        }

        // Topic commands
        if (class_exists('NewTopicCmd')) {
            $registry->register(ActionConst::NEWTOPIC, new NewTopicCmd());
        }
        if (class_exists('EditTopicCmd')) {
            $registry->register(ActionConst::EDITTOPIC, new EditTopicCmd());
        }
        if (class_exists('DeleteTopicCmd')) {
            $registry->register(ActionConst::DELETETOPIC, new DeleteTopicCmd());
        }
        if (class_exists('ListTopicsCmd')) {
            $registry->register('default_topic', new ListTopicsCmd());
        }

        // Post commands
        if (class_exists('NewPostCmd')) {
            $registry->register(ActionConst::NEWPOST, new NewPostCmd());
        }
        if (class_exists('EditPostCmd')) {
            $registry->register(ActionConst::EDITPOST, new EditPostCmd());
        }
        if (class_exists('DeletePostCmd')) {
            $registry->register(ActionConst::DELETEPOST, new DeletePostCmd());
        }
        if (class_exists('ListPostsCmd')) {
            $registry->register('default_post', new ListPostsCmd());
        }

        // Page commands
        if (class_exists('NewPageCmd')) {
            $registry->register(ActionConst::NEWPAGE, new NewPageCmd());
        }
        if (class_exists('EditPageCmd')) {
            $registry->register(ActionConst::EDITPAGE, new EditPageCmd());
        }
        if (class_exists('DeletePageCmd')) {
            $registry->register(ActionConst::DELETEPAGE, new DeletePageCmd());
        }
        if (class_exists('ListPagesCmd')) {
            $registry->register('default_page', new ListPagesCmd());
        }

        // User commands
        if (class_exists('NewUserCmd')) {
            $registry->register(ActionConst::NEWUSER, new NewUserCmd());
        }
        if (class_exists('EditUserCmd')) {
            $registry->register(ActionConst::EDITUSER, new EditUserCmd());
        }
        if (class_exists('DeleteUserCmd')) {
            $registry->register(ActionConst::DELETEUSER, new DeleteUserCmd());
        }
        if (class_exists('ListUsersCmd')) {
            $registry->register('default_user', new ListUsersCmd());
        }

        // Media commands
        if (class_exists('NewMediaCmd')) {
            $registry->register(ActionConst::NEWMEDIA, new NewMediaCmd());
        }
        if (class_exists('EditMediaCmd')) {
            $registry->register(ActionConst::EDITMEDIA, new EditMediaCmd());
        }
        if (class_exists('DeleteMediaCmd')) {
            $registry->register(ActionConst::DELETEMEDIA, new DeleteMediaCmd());
        }
        if (class_exists('ListMediaCmd')) {
            $registry->register('default_media', new ListMediaCmd());
        }

        // Plugin commands
        if (class_exists('InstallPluginCmd')) {
            $registry->register(ActionConst::INSTALLPLUGIN, new InstallPluginCmd());
        }
        if (class_exists('ActivatePluginCmd')) {
            $registry->register(ActionConst::ACTIVATEPLUGIN, new ActivatePluginCmd());
        }
        if (class_exists('DeactivatePluginCmd')) {
            $registry->register(ActionConst::DEACTIVATEPLUGIN, new DeactivatePluginCmd());
        }
        if (class_exists('DeletePluginCmd')) {
            $registry->register(ActionConst::DELETEPLUGIN, new DeletePluginCmd());
        }
        if (class_exists('ListPluginsCmd')) {
            $registry->register('default_plugin', new ListPluginsCmd());
        }

        // Theme commands
        if (class_exists('NewThemeCmd')) {
            $registry->register(ActionConst::NEWTHEME, new NewThemeCmd());
        }
        if (class_exists('InstallThemeCmd')) {
            $registry->register(ActionConst::INSTALLTHEME, new InstallThemeCmd());
        }
        if (class_exists('EditThemeCmd')) {
            $registry->register(ActionConst::EDITTHEME, new EditThemeCmd());
        }
        if (class_exists('DeleteThemeCmd')) {
            $registry->register(ActionConst::DELETETHEME, new DeleteThemeCmd());
        }
        if (class_exists('ActivateThemeCmd')) {
            $registry->register(ActionConst::ACTIVATETHEME, new ActivateThemeCmd());
        }
        if (class_exists('DeactivateThemeCmd')) {
            $registry->register(ActionConst::DEACTIVATETHEME, new DeactivateThemeCmd());
        }
        if (class_exists('ListThemesCmd')) {
            $registry->register('default_theme', new ListThemesCmd());
        }

        self::$services['adminActionRegistry'] = $registry;
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
            } catch (\Exception $e) {
                // Whoops error handler initialization failed silently
            }
        }
    }
}
