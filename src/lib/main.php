<?php

declare(strict_types=1);

(version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);
/**
 * Main.php file
 * Initialize main engine, define constants, and object instantiated
 * include functions needed by application
 * 
 * @category main.php file
 * @author   M.Noermoehammad
 * @license  https://opensource.org/licenses/MIT MIT License
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
require __DIR__ . '/options.php';
require __DIR__ . '/common.php';

if (!file_exists(APP_ROOT . 'config.php')) {

    if (is_dir(APP_ROOT . 'install')) {

        header("Location: " . APP_PROTOCOL . "://" . APP_HOSTNAME . dirname(dirname(htmlspecialchars($_SERVER['PHP_SELF']))) . DS . 'install');
        exit();
    }
    
} else {

    $config = include __DIR__ . '/../config.php';

    $db_host = isset($config['db']['host']) ? $config['db']['host'] : "";
    $db_user = isset($config['db']['user']) ? $config['db']['user'] : "";
    $db_pwd  = isset($config['db']['pass']) ? $config['db']['pass'] : "";
    $db_name = isset($config['db']['name']) ? $config['db']['name'] : "";
    $db_port = isset($config['db']['port']) ? $config['db']['port'] : "";

    $app_email = isset($config['app']['emamil']) ? $config['app']['email'] : "";
    $app_url   = isset($config['app']['url']) ? $config['app']['url'] : "";
    $app_key   = isset($config['app']['key']) ? $config['app']['key'] : "";

    #================================== call functions in directory lib/utility ===========================================
    $directory = new RecursiveDirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . 'utility' . DIRECTORY_SEPARATOR, FilesystemIterator::FOLLOW_SYMLINKS);
    $filter = new RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {

        // Allow recursion
        if ($iterator->hasChildren()) {
            return true;
        }

        // skip hidden files and directories
        if ($current->getFilename()[0] == '.') {
            return false;
        }

        $subdir = $current->getFilename() == __DIR__ . DIRECTORY_SEPARATOR . 'utility' . DIRECTORY_SEPARATOR;

        return ($current->isDir()) ? $subdir : strpos($current->getFilename(), '.php');
    });

    $iterator = new RecursiveIteratorIterator($filter);

    foreach ($iterator as $file) {

        include $file->getPathname();
    }

    #====================End of call functions in directory lib/utility=====================================================

    // check if loader is exists
    if (file_exists(APP_ROOT . APP_LIBRARY . DIRECTORY_SEPARATOR . 'Autoloader.php')) {

        include __DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php';
    }

    if (is_readable(APP_ROOT . APP_LIBRARY . DIRECTORY_SEPARATOR . 'vendor/autoload.php')) {

        include_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
    }

    Autoloader::setBaseDir(APP_ROOT);
    // load libraries necessary by system
    Autoloader::addClassDir(array(
        APP_ROOT . APP_LIBRARY . DIRECTORY_SEPARATOR . 'core'    . DIRECTORY_SEPARATOR,
        APP_ROOT . APP_LIBRARY . DIRECTORY_SEPARATOR . 'dao'     . DIRECTORY_SEPARATOR,
        APP_ROOT . APP_LIBRARY . DIRECTORY_SEPARATOR . 'event'   . DIRECTORY_SEPARATOR,
        APP_ROOT . APP_LIBRARY . DIRECTORY_SEPARATOR . 'app'     . DIRECTORY_SEPARATOR,
        APP_ROOT . APP_LIBRARY . DIRECTORY_SEPARATOR . 'provider'. DIRECTORY_SEPARATOR
    ));

    x_frame_option();
    x_content_type_options();
    x_xss_protection();
    strict_transport_security();
    call_htmlpurifier();
    get_server_load();
    whoops_error();
    content_security_policy($app_url);

    #===================== RULES ==========================
    /**
     * rules adapted by dispatcher to route request 
     * if permalink for SEO-URL friendly enabled
     * */
    /****************************************************** 

     ### '/picture/some-text/51' 
    'picture' => "/picture/(?'text'[^/]+)/(?'id'\d+)",    
    
     ### '/album/album-slug'
    'album' => "/album/(?'album'[\w\-]+)",              
    
     ### '/category/category-slug'
    'category' => "/category/(?'category'[\w\-]+)", 
    
     ### 'archive/12/2017
     'archive' => "/archive/[0-9]{2}/[0-9]{4}",

     ### 'tag/tag-slug'
     'tag' => "/tag/(?'tag'[\w\-]+)"

     ### '/blog?p=255'
    'blog' => "/blog([^/]*)",                       
    
     ### '/page/about', '/page/contact'
    'page' => "/page/(?'page'[^/]+)
     
    ### '/post/60/post-slug'
    'single' => "/post/(?'id'\d+)/(?'post'[\w\-]+)",     
    
     ### '/'
    'home' => "/"                                        

     ******************************************************/
    $rules = array(

        'home'     => "/",
        'category' => "/category/(?'category'[\w\-]+)",
        'archive'  => "/archive/[0-9]{2}/[0-9]{4}",
        'blog'     => "/blog([^/]*)",
        'page'     => "/page/(?'page'[^/]+)",
        'single'   => "/post/(?'id'\d+)/(?'post'[\w\-]+)",
        'search'   => "(?'search'[\w\-]+)",
        'tag'      => "/tag/(?'tag'[\w\-]+)"

    );

    #==================== END OF RULES =======================

    #====== an instantiation of Database connection ==========
    $dbc = class_exists('DbFactory') ? DbFactory::connect(['mysql:host=' . $db_host . ';port=' . $db_port . ';dbname=' . $db_name, $db_user, $db_pwd]) : "";

    #====== an instantiation of scriptlog cipher key =========
    $cipher_key = class_exists('ScriptlogCryptonize') ? ScriptlogCryptonize::scriptlogCipherKey() : "";

    #====== an instantiation of scriptlog request path =======
    $uri = class_exists('RequestPath') ? new RequestPath() : "";

    // Register rules of routes, an instance of database connection, cipher key for cryptography and uri requested
    class_exists('Registry') ? Registry::setAll(array('dbc' => $dbc,  'key' => $cipher_key, 'route' => $rules, 'uri' => $uri)) : "";

/* an instances of class that necessary for the system
 * please do not change this below variable 
 * these are collection of objects or instances of classes 
 * that will be run by the system.
 * 
 * @var $sessionMaker invoked by Session Cookies
 * @var $searchPost invoked by search functionality
 * @var $sanitizer adapted by sanitize functionality
 * @var $userDao, $userToken, $validator, $authenticator invoked by login and authentication systems
 * 
 */
    $sessionMaker = class_exists('SessionMaker') ? new SessionMaker(set_session_cookies_key($app_email, $app_key)) : "";
    $searchPost = class_exists('SearchFinder') ? new SearchFinder() : "";
    $sanitizer = class_exists('Sanitize') ? new Sanitize() : "";
    $userDao = class_exists('UserDao') ? new UserDao() : "";
    $userToken = class_exists('UserTokenDao') ? new UserTokenDao() : "";
    $validator = class_exists('FormValidator') ? new FormValidator() : "";
    $authenticator = class_exists('Authentication') ? new Authentication($userDao, $userToken, $validator) : "";
    $ubench = class_exists('Ubench') ? new Ubench() : "";
    $dispatcher = class_exists('Dispatcher') ? new Dispatcher() : "";

}

is_a($sessionMaker, 'SessionMaker') ? session_set_save_handler($sessionMaker, true) : "";
session_save_path(__DIR__ . '/utility/.sessions' . DS);
register_shutdown_function('session_write_close');

if (function_exists('start_session_on_site') && (!start_session_on_site($sessionMaker))) {

    ob_start();
}

$errors = [];