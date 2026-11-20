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
 * The class is a composition root: it wires concrete implementations together
 * and exposes them through a single AppContext. Business logic does not live
 * here.
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
use Scriptlog\Handler\Admin\Media\DeleteMediaCmd;
use Scriptlog\Handler\Admin\Media\EditMediaCmd;
use Scriptlog\Handler\Admin\Media\ListMediaCmd;
use Scriptlog\Handler\Admin\Media\NewMediaCmd;
use Scriptlog\Handler\Admin\Page\DeletePageCmd;
use Scriptlog\Handler\Admin\Page\EditPageCmd;
use Scriptlog\Handler\Admin\Page\ListPagesCmd;
use Scriptlog\Handler\Admin\Page\NewPageCmd;
use Scriptlog\Handler\Admin\Plugin\ActivatePluginCmd;
use Scriptlog\Handler\Admin\Plugin\DeactivatePluginCmd;
use Scriptlog\Handler\Admin\Plugin\DeletePluginCmd;
use Scriptlog\Handler\Admin\Plugin\InstallPluginCmd;
use Scriptlog\Handler\Admin\Plugin\ListPluginsCmd;
use Scriptlog\Handler\Admin\Post\DeletePostCmd;
use Scriptlog\Handler\Admin\Post\EditPostCmd;
use Scriptlog\Handler\Admin\Post\ListPostsCmd;
use Scriptlog\Handler\Admin\Post\NewPostCmd;
use Scriptlog\Handler\Admin\Theme\ActivateThemeCmd;
use Scriptlog\Handler\Admin\Theme\DeactivateThemeCmd;
use Scriptlog\Handler\Admin\Theme\DeleteThemeCmd;
use Scriptlog\Handler\Admin\Theme\EditThemeCmd;
use Scriptlog\Handler\Admin\Theme\InstallThemeCmd;
use Scriptlog\Handler\Admin\Theme\ListThemesCmd;
use Scriptlog\Handler\Admin\Theme\NewThemeCmd;
use Scriptlog\Handler\Admin\Topic\DeleteTopicCmd;
use Scriptlog\Handler\Admin\Topic\EditTopicCmd;
use Scriptlog\Handler\Admin\Topic\ListTopicsCmd;
use Scriptlog\Handler\Admin\Topic\NewTopicCmd;
use Scriptlog\Handler\Admin\User\DeleteUserCmd;
use Scriptlog\Handler\Admin\User\EditUserCmd;
use Scriptlog\Handler\Admin\User\ListUsersCmd;
use Scriptlog\Handler\Admin\User\NewUserCmd;
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

    /**
     * Keys that may leave this class through AppContext.
     *
     * Anything not listed here stays internal to the bootstrap, even though it
     * is instantiated.
     *
     * @var array<int, string>
     */
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
        'adminActionRegistry',
    ];

    /**
     * Initializes the application environment.
     *
     * Performs the full bootstrap sequence: configuration, utilities,
     * service containerization, and security header enforcement.
     *
     * @param string $appRoot The absolute path to the application root directory.
     * @return AppContext Merged collection of configuration variables and service instances.
     */
    public static function initialize(string $appRoot): AppContext
    {
        $coreVars = self::loadConfiguration($appRoot);

        require_once __DIR__ . '/../../lib/utility-loader.php';

        $services = self::initializeServices($coreVars);

        try {
            self::applySecurity();
        } catch (\Throwable $e) {
            error_log('Security header application failed: ' . $e->getMessage());
        }

        $exported = array_intersect_key(
            array_merge($coreVars, $services),
            array_flip(self::$allowedExportVars)
        );

        return new AppContext($exported);
    }

    /**
     * Loads the configuration file and extracts required environment variables.
     *
     * @param string $appRoot Path to search for config.php.
     * @return array<string, string> Array containing db_host, app_url, cipher_key, etc.
     * @throws \Exception When APP_KEY is missing or empty.
     */
    private static function loadConfiguration(string $appRoot): array
    {
        if (!file_exists($appRoot . 'config.php')) {
            return [];
        }

        self::$config = require $appRoot . 'config.php';

        $dbHost   = self::$config['db']['host'] ?? '';
        $dbUser   = self::$config['db']['user'] ?? '';
        $dbPwd    = self::$config['db']['pass'] ?? '';
        $dbName   = self::$config['db']['name'] ?? '';
        $dbPort   = self::$config['db']['port'] ?? '';
        $dbPrefix = self::$config['db']['prefix'] ?? '';

        $appEmail = self::$config['app']['email'] ?? '';
        $appUrl   = self::$config['app']['url'] ?? '';
        $appKey   = self::$config['app']['key'] ?? '';

        if (empty($appKey)) {
            throw new \Exception('Security Risk: APP_KEY is missing from environment.');
        }

        $cipherKey = class_exists('ScriptlogCryptonize') ? ScriptlogCryptonize::scriptlogCipherKey() : '';

        return [
            'db_host' => $dbHost,
            'db_user' => $dbUser,
            'db_pwd' => $dbPwd,
            'db_name' => $dbName,
            'db_port' => $dbPort,
            'db_prefix' => $dbPrefix,
            'app_email' => $appEmail,
            'app_url' => $appUrl,
            'app_key' => $appKey,
            'cipher_key' => $cipherKey,
        ];
    }

    /**
     * Orchestrates service instantiation and global registry setup.
     *
     * Registry entries are populated before the DAOs that depend on them are
     * constructed. FrontService resolves its DAOs from the Registry at
     * construction time, so it is built after the content DAOs are registered.
     *
     * @param array<string, string> $coreVars Extracted configuration variables.
     * @return array<string, object|null> Collection of ready-to-use services.
     * @uses DbFactory::connect()
     * @uses Registry::setAll()
     */
    private static function initializeServices(array $coreVars): array
    {
        $dbc = self::createDatabaseConnection($coreVars);

        self::initializeRegistry($dbc, $coreVars, self::defineRoutingRules());

        list($userDao, $userToken, $validator, $sanitizer, $configDao, $configService) = self::createBaseDaos($dbc);

        $sessionMaker = self::createSession($coreVars);
        $authenticator = self::createAuthenticator($dbc, $userDao, $userToken, $validator);
        $themeRenderer = self::createThemeRenderer($dbc);

        if ($themeRenderer && class_exists('HandleRequest')) {
            HandleRequest::setThemeRenderer($themeRenderer);
        }

        list($mediaDao, $downloadService, $downloadController) = self::createDownloadChain($dbc);
        list($postDao, $pageDao, $topicDao) = self::createContentDaos($dbc);

        self::registerContentServices($mediaDao, $downloadService, $downloadController, $postDao, $pageDao, $topicDao);

        $frontService = self::createFrontService();
        self::setRegistryValue('frontService', $frontService);

        $dispatcher = self::createDispatcher($dbc, $themeRenderer);

        self::registerHandlers($themeRenderer, $downloadController);

        $adminActionRegistry = self::createAdminActionRegistry();
        $i18n = self::initializeI18n();

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
            'themeRenderer' => $themeRenderer,
            'mediaDao' => $mediaDao,
            'downloadService' => $downloadService,
            'downloadController' => $downloadController,
            'frontService' => $frontService,
            'postDao' => $postDao,
            'pageDao' => $pageDao,
            'topicDao' => $topicDao,
            'adminActionRegistry' => $adminActionRegistry,
            'i18n' => $i18n,
        ];

        return self::$services;
    }

    /**
     * Opens the database connection described by the configuration.
     *
     * A failed connection is not fatal: the frontend degrades to
     * configuration-only services instead of failing the whole request.
     *
     * @param array<string, string> $coreVars Extracted configuration variables.
     * @return Db|string|null Database wrapper, or an empty string/null when unavailable.
     */
    private static function createDatabaseConnection(array $coreVars)
    {
        $dbc = '';

        if (
            class_exists('DbFactory')
            && !empty($coreVars['db_host'])
            && !empty($coreVars['db_user'])
            && !empty($coreVars['db_name'])
        ) {
            try {
                $dbc = DbFactory::connect([
                    'mysql:host=' . $coreVars['db_host'] . ';port=' . $coreVars['db_port'] . ';dbname=' . $coreVars['db_name'] . ';charset=utf8mb4',
                    $coreVars['db_user'],
                    $coreVars['db_pwd'],
                ]);
            } catch (\Exception $e) {
                $dbc = '';
            }
        }

        if (
            !empty($dbc)
            && !empty($coreVars['db_prefix'])
            && method_exists($dbc, 'setTablePrefix')
        ) {
            $dbc->setTablePrefix($coreVars['db_prefix']);
        }

        return $dbc;
    }

    /**
     * Returns the routing table consumed by the dispatcher.
     *
     * @return array<string, string> Route name to regular expression.
     */
    private static function defineRoutingRules(): array
    {
        return [
            'home' => '/',
            'category' => "/category/(?'category'[\w\-]+)",
            'archive' => '/archive/[0-9]{2}/[0-9]{4}',
            'archives' => '/archives',
            'blog' => '/blog([^/]*)',
            'page' => "/page/(?'page'[^/]+)",
            'single' => "/post/(?'id'\d+)/(?'post'[\w\-]+)",
            'tag' => "/tag/(?'tag'[\w\- ]+)",
            'search' => '/search',
            'privacy' => '/privacy',
            'locale' => '/locale',
            'download' => "/download/(?'identifier'[a-f0-9\-]+)",
            'download_file' => "/download/(?'identifier'[a-f0-9\-]+)/file",
        ];
    }

    /**
     * Seeds the global Registry with the connection, cipher key and routes.
     *
     * @param Db|string|null $dbc Connected database wrapper, or an empty string/null.
     * @param array<string, string> $coreVars Extracted configuration variables.
     * @param array<string, string> $rules Routing table.
     * @return void
     */
    private static function initializeRegistry($dbc, array $coreVars, array $rules): void
    {
        if (!class_exists('Registry')) {
            return;
        }

        Registry::setAll([
            'dbc' => $dbc,
            'key' => $coreVars['cipher_key'] ?? '',
            'route' => $rules,
            'uri' => class_exists('RequestPath') ? new RequestPath() : null,
        ]);
    }

    /**
     * Builds the DAOs and validator that only need a database connection.
     *
     * @param Db|string|null $dbc Connected database wrapper, or an empty string/null.
     * @return array<int, object|null> [userDao, userToken, validator, sanitizer, configDao, configService]
     */
    private static function createBaseDaos($dbc): array
    {
        if (empty($dbc)) {
            return [null, null, null, null, null, null];
        }

        $userDao = class_exists('UserDao') ? new UserDao() : null;
        $userToken = class_exists('UserTokenDao') ? new UserTokenDao() : null;
        $validator = class_exists('FormValidator') ? new FormValidator() : null;
        $sanitizer = class_exists('Sanitize') ? new Sanitize() : null;
        $configDao = class_exists('ConfigurationDao') ? new ConfigurationDao() : null;

        $configService = ($configDao && $validator && $sanitizer)
            ? new ConfigurationService($configDao, $validator, $sanitizer)
            : null;

        return [$userDao, $userToken, $validator, $sanitizer, $configDao, $configService];
    }

    /**
     * Starts the encrypted session handler used by the whole request.
     *
     * @param array<string, string> $coreVars Extracted configuration variables.
     * @return SessionMaker|null The session handler, or null when unavailable.
     */
    private static function createSession(array $coreVars): ?SessionMaker
    {
        $sessionMaker = null;

        if (class_exists('SessionMaker')) {
            try {
                if (!headers_sent() || PHP_SAPI === 'cli') {
                    $sessionMaker = new SessionMaker(
                        set_session_cookies_key($coreVars['app_email'] ?? '', $coreVars['app_key'] ?? '')
                    );
                }
            } catch (\Exception $e) {
                // Session creation failed silently - acceptable during early bootstrap.
            }
        }

        if (!$sessionMaker) {
            return null;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_save_path(resolve_session_save_path());
        session_set_save_handler($sessionMaker, true);
        register_shutdown_function('session_write_close');

        if (function_exists('start_session_on_site')) {
            start_session_on_site($sessionMaker);
        }

        return $sessionMaker;
    }

    /**
     * Creates the authentication service.
     *
     * @param Db|string|null $dbc Connected database wrapper, or an empty string/null.
     * @param UserDao|null $userDao User data access object.
     * @param UserTokenDao|null $userToken Persistent token data access object.
     * @param FormValidator|null $validator Form validator.
     * @return Authentication|null
     */
    private static function createAuthenticator($dbc, $userDao, $userToken, $validator): ?Authentication
    {
        if (!class_exists('Authentication') || empty($dbc)) {
            return null;
        }

        try {
            return new Authentication($userDao, $userToken, $validator);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Creates the theme renderer bound to the global context.
     *
     * @param Db|string|null $dbc Connected database wrapper, or an empty string/null.
     * @return ThemeRenderer|null
     */
    private static function createThemeRenderer($dbc): ?ThemeRenderer
    {
        // The connection is the Db wrapper, not a PDO instance, so the only
        // meaningful guard is "a connection object exists" (see the docblock
        // contract above). empty() is false for any object.
        if (!class_exists('ThemeRenderer') || empty($dbc)) {
            return null;
        }

        try {
            return ThemeRenderer::fromGlobalContext();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Creates the media download chain (DAO, service, controller).
     *
     * @param Db|string|null $dbc Connected database wrapper, or an empty string/null.
     * @return array<int, object|null> [mediaDao, downloadService, downloadController]
     */
    private static function createDownloadChain($dbc): array
    {
        if (empty($dbc)) {
            return [null, null, null];
        }

        $mediaDao = class_exists('MediaDao') ? new MediaDao() : null;
        $downloadModel = class_exists('DownloadModel') ? new DownloadModel() : null;

        $downloadService = ($downloadModel && $mediaDao)
            ? new DownloadService($downloadModel, $mediaDao)
            : null;

        $downloadController = $downloadService
            ? new DownloadController($downloadService)
            : null;

        return [$mediaDao, $downloadService, $downloadController];
    }

    /**
     * Creates the DAOs that serve posts, pages and topics.
     *
     * @param Db|string|null $dbc Connected database wrapper, or an empty string/null.
     * @return array<int, object|null> [postDao, pageDao, topicDao]
     */
    private static function createContentDaos($dbc): array
    {
        if (empty($dbc)) {
            return [null, null, null];
        }

        return [
            class_exists('PostDao') ? new PostDao() : null,
            class_exists('PageDao') ? new PageDao() : null,
            class_exists('TopicDao') ? new TopicDao() : null,
        ];
    }

    /**
     * Publishes the content services in the global Registry.
     *
     * @param MediaDao|null $mediaDao Media data access object.
     * @param DownloadService|null $downloadService Media download service.
     * @param DownloadController|null $downloadController Media download controller.
     * @param PostDao|null $postDao Post data access object.
     * @param PageDao|null $pageDao Page data access object.
     * @param TopicDao|null $topicDao Topic data access object.
     * @return void
     */
    private static function registerContentServices($mediaDao, $downloadService, $downloadController, $postDao, $pageDao, $topicDao): void
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

    /**
     * Creates the frontend service graph.
     *
     * @return FrontService|null
     */
    private static function createFrontService(): ?FrontService
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

    /**
     * Creates the request dispatcher.
     *
     * @param Db|string|null $dbc Connected database wrapper, or an empty string/null.
     * @param ThemeRenderer|null $themeRenderer Theme renderer.
     * @return Dispatcher|null
     */
    private static function createDispatcher($dbc, $themeRenderer): ?Dispatcher
    {
        if (!class_exists('Dispatcher') || empty($dbc)) {
            return null;
        }

        try {
            return new Dispatcher($themeRenderer);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Registers the frontend query-string handlers.
     *
     * @param ThemeRenderer|null $themeRenderer Theme renderer.
     * @param DownloadController|null $downloadController Media download controller.
     * @return void
     */
    private static function registerHandlers($themeRenderer, $downloadController): void
    {
        if (!$themeRenderer || !class_exists('HandlerRegistry')) {
            return;
        }

        $handlerRegistry = new HandlerRegistry();

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

        self::setRegistryValue('handlerRegistry', $handlerRegistry);
    }

    /**
     * Registers the admin action commands by class name.
     *
     * Commands are only constructed when an admin request actually dispatches
     * that action (AdminActionRegistry::has()/execute()). Frontend requests
     * never read this registry, so eager construction cost ~1.7 ms and ~35
     * class loads on every page view.
     *
     * @return AdminActionRegistry|null
     */
    private static function createAdminActionRegistry(): ?AdminActionRegistry
    {
        if (!class_exists('AdminActionRegistry')) {
            return null;
        }

        $registry = new AdminActionRegistry();

        foreach (self::adminActionCommands() as $action => $commandClass) {
            $registry->registerClass($action, $commandClass);
        }

        return $registry;
    }

    /**
     * Maps admin action constants to their command implementations.
     *
     * The registry stores these class names and instantiates them on first
     * dispatch.
     *
     * @return array<string, class-string> Action constant to command class.
     */
    private static function adminActionCommands(): array
    {
        return [
            // Comments
            ActionConst::EDITCOMMENT => EditCommentCmd::class,
            ActionConst::DELETECOMMENT => DeleteCommentCmd::class,
            'default' => ListCommentsCmd::class,
            // Topics
            ActionConst::NEWTOPIC => NewTopicCmd::class,
            ActionConst::EDITTOPIC => EditTopicCmd::class,
            ActionConst::DELETETOPIC => DeleteTopicCmd::class,
            'default_topic' => ListTopicsCmd::class,
            // Posts
            ActionConst::NEWPOST => NewPostCmd::class,
            ActionConst::EDITPOST => EditPostCmd::class,
            ActionConst::DELETEPOST => DeletePostCmd::class,
            'default_post' => ListPostsCmd::class,
            // Pages
            ActionConst::NEWPAGE => NewPageCmd::class,
            ActionConst::EDITPAGE => EditPageCmd::class,
            ActionConst::DELETEPAGE => DeletePageCmd::class,
            'default_page' => ListPagesCmd::class,
            // Users
            ActionConst::NEWUSER => NewUserCmd::class,
            ActionConst::EDITUSER => EditUserCmd::class,
            ActionConst::DELETEUSER => DeleteUserCmd::class,
            'default_user' => ListUsersCmd::class,
            // Media
            ActionConst::NEWMEDIA => NewMediaCmd::class,
            ActionConst::EDITMEDIA => EditMediaCmd::class,
            ActionConst::DELETEMEDIA => DeleteMediaCmd::class,
            'default_media' => ListMediaCmd::class,
            // Plugins
            ActionConst::INSTALLPLUGIN => InstallPluginCmd::class,
            ActionConst::ACTIVATEPLUGIN => ActivatePluginCmd::class,
            ActionConst::DEACTIVATEPLUGIN => DeactivatePluginCmd::class,
            ActionConst::DELETEPLUGIN => DeletePluginCmd::class,
            'default_plugin' => ListPluginsCmd::class,
            // Themes
            ActionConst::NEWTHEME => NewThemeCmd::class,
            ActionConst::INSTALLTHEME => InstallThemeCmd::class,
            ActionConst::EDITTHEME => EditThemeCmd::class,
            ActionConst::DELETETHEME => DeleteThemeCmd::class,
            ActionConst::ACTIVATETHEME => ActivateThemeCmd::class,
            ActionConst::DEACTIVATETHEME => DeactivateThemeCmd::class,
            'default_theme' => ListThemesCmd::class,
        ];
    }

    /**
     * Initializes the frontend translator.
     *
     * @return I18nManager|null
     */
    private static function initializeI18n(): ?I18nManager
    {
        if (!class_exists('I18nManager')) {
            return null;
        }

        $i18n = I18nManager::getInstance();
        $i18n->initialize();

        return $i18n;
    }

    /**
     * Stores one value in the global Registry when the class is available.
     *
     * @param string $key Registry key.
     * @param mixed $value Value to store.
     * @return void
     */
    private static function setRegistryValue(string $key, $value): void
    {
        if (class_exists('Registry')) {
            Registry::set($key, $value);
        }
    }

    /**
     * Configures HTTP response headers and global security handlers.
     *
     * The shared helpers in lib/utility/secure-http-headers.php are loaded by
     * utility-loader.php before this method runs, and are also used by the
     * early page-cache path, so a cached response carries byte-identical
     * headers.
     *
     * @return void
     */
    private static function applySecurity(): void
    {
        scriptlog_define_csp_nonce();
        scriptlog_send_security_headers(self::$config['app']['url'] ?? '');

        self::initializePostSecurity();
    }

    /**
     * Runs the remaining per-request security hooks.
     *
     * HTMLPurifier is lazy-loaded at its use site (purify_dirty_html()), so
     * pages without content skip it entirely.
     *
     * @return void
     */
    private static function initializePostSecurity(): void
    {
        if (function_exists('get_server_load')) {
            try {
                get_server_load();
            } catch (\Throwable $e) {
                // Server load check failed silently.
            }
        }

        if (function_exists('whoops_error')) {
            try {
                whoops_error();
            } catch (\Exception $e) {
                // Whoops error handler initialization failed silently.
            }
        }
    }
}
