<?php
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

(version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);

$config = array();

if (! file_exists(APP_ROOT.'config.php')) {
    
    if (is_dir(APP_ROOT . 'install')) {

        header("Location: ".APP_PROTOCOL."://".APP_HOSTNAME.dirname(htmlspecialchars($_SERVER['PHP_SELF'])).DS.'install');
        exit();
         
    }

} else {

$config = include __DIR__ . '/../config.php';

#================================== call functions in directory lib/utility ===========================================
$directory = new RecursiveDirectoryIterator(__DIR__ . DS .'utility'. DS, FilesystemIterator::FOLLOW_SYMLINKS);
$filter = new RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
    
    // Allow recursion
    if ($iterator->hasChildren()) {
        return true;
    }

    // skip hidden files and directories
    if ($current->getFilename()[0] === '.') {
        return false;
    }
    
    if ($current->isDir()) {
        
        # only recurse into intended subdirectories
        return $current->getFilename() === __DIR__ . DS .'utility'. DS;
        
    } else {
        
        # only invoke files of interest
        return strpos($current->getFilename(), '.php');
        
    }
    
});
    
$iterator = new RecursiveIteratorIterator($filter); 

foreach ($iterator as $file) {
    
    include $file->getPathname();
    
}

#====================End of call functions in directory lib/utility=====================================================

// check if loader is exists
if (file_exists(APP_ROOT.APP_LIBRARY.DS.'Autoloader.php')) {

    include __DIR__ . DS . 'Autoloader.php';

}

if (is_readable(APP_ROOT.APP_LIBRARY.DS.'vendor/autoload.php')) {

    include_once __DIR__ . DS . 'vendor/autoload.php';
    
}

Autoloader::setBaseDir(APP_ROOT);
// load libraries necessary by system
Autoloader::addClassDir(array(
  APP_ROOT . APP_LIBRARY . DS . 'core'    . DS,  
  APP_ROOT . APP_LIBRARY . DS . 'dao'     . DS,
  APP_ROOT . APP_LIBRARY . DS . 'event'   . DS,
  APP_ROOT . APP_LIBRARY . DS . 'app'     . DS,
  APP_ROOT . APP_LIBRARY . DS . 'provider'. DS
));

x_frame_option();
x_content_type_options();
x_xss_protection();
strict_transport_security();
call_htmlpurifier();
get_server_load();
whoops_error();
content_security_policy(isset($config['app']['url']) ? $config['app']['url'] : "");

#===================== RULES ==========================

// rules adapted by dispatcher to route request

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
$dbc = (isset($config['db']['host']) || isset($config['db']['name']) || isset($config['db']['user']) || isset($config['db']['pass'])) ? DbFactory::connect(['mysql:host='.$config['db']['host'].';dbname='.$config['db']['name'], $config['db']['user'], $config['db']['pass']]) : "";

#====== an instantiation of scriptlog cipher key =========
$cipher_key = ScriptlogCryptonize::scriptlogCipherKey();

#====== an instantiation of scriptlog request path =======
$uri = new RequestPath();

// Register rules of routes, an instance of database connection, cipher key for cryptography and uri requested
Registry::setAll(array('dbc' => $dbc,  'key' => $cipher_key, 'route' => $rules, 'uri'=>$uri));

/* an instances of class that necessary for the system
 * please do not change this below variable 
 * these are collection of objects or instances of classes 
 * that will be run by the system.
 * 
 * @var $searchPost invoked by search functionality
 * @var $sanitizer adapted by sanitize functionality
 * @var $userDao, $validator, $authenticator, $ubench --
 * 
 */
$sessionMaker = new SessionMaker(set_session_cookies_key((isset($config['app']['email']) ? $config['app']['email'] : ""), (isset($config['app']['key']) ? $config['app']['key'] : "")));
$searchPost = new SearchFinder();
$sanitizer = new Sanitize();
$userDao = new UserDao();
$userToken = new UserTokenDao();
$validator = new FormValidator();
$authenticator = new Authentication($userDao, $userToken, $validator);
$ubench = new Ubench();
$dispatcher = new Dispatcher();

}

session_set_save_handler($sessionMaker, true);
session_save_path(__DIR__ . '/utility/.sessions'.DS);
register_shutdown_function('session_write_close');

if (!start_session_on_site($sessionMaker)) {

    ob_start();

}

$errors = [];