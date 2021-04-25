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
#ini_set("session.cookie_secure", 1);  
#ini_set("session.cookie_lifetime", 604800);  
ini_set("session.cookie_httponly", 1);
#ini_set("session.use_cookies", 1);
ini_set("session.use_only_cookies", 1);
#ini_set("session.use_strict_mode", 1);
#ini_set("session.use_trans_sid", 0);
ini_set('session.save_handler', 'files');
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 1440);
ini_set('session.gc_probability',1);

#date_default_timezone_set("GMT");

require __DIR__ . '/common.php';

$config = null;

if ( is_readable(APP_ROOT.'config.php') ) {
    
  $config = require __DIR__ . '/../config.php';
    
} else {
     
    if (is_dir(APP_ROOT . 'install')) {

        header("Location: ".APP_PROTOCOL."://".APP_HOSTNAME.dirname($_SERVER['PHP_SELF']).DS.'install');
        exit();
         
    }
    
}

#================================== call functions in directory lib/utility ===========================================
$function_directory = new RecursiveDirectoryIterator(__DIR__ . DS .'utility'. DS, FilesystemIterator::FOLLOW_SYMLINKS);
$filter_iterator = new RecursiveCallbackFilterIterator($function_directory, function ($current, $key, $iterator){
    
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
    
$files_dir_iterator = new RecursiveIteratorIterator($filter_iterator); 

foreach ($files_dir_iterator as $file) {
    
    include $file->getPathname();
    
}

#====================End of call functions in directory lib/utility=====================================================

// check if loader is exists
if (is_readable(APP_ROOT.APP_LIBRARY.DS.'vendor/autoload.php')) {

    require_once __DIR__ . DS . 'vendor/autoload.php';
    
}

if (file_exists(APP_ROOT.APP_LIBRARY.DS.'Autoloader.php')) {

    require __DIR__ . DS . 'Autoloader.php';

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

call_htmlpurifier();

get_server_load();

whoops_error();

content_security_policy($config['app']['url']);

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
     'archive' => "/archive/[0-9]{2}/[0-9]{2}/[0-9]{4}",

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
    'archive'  => "/archive/[0-9]{2}/[0-9]{2}/[0-9]{4}",
    'blog'     => "/blog([^/]*)",
    'page'     => "/page/(?'page'[^/]+)",
    'single'   => "/post/(?'id'\d+)/(?'post'[\w\-]+)",
    'search'   => "(?'search'[\w\-]+)"
    
);

#==================== END OF RULES =======================

#====== an instantiation of Database connection ==========
$dbc = DbFactory::connect(['mysql:host='.$config['db']['host'].';dbname='.$config['db']['name'], $config['db']['user'], $config['db']['pass']]);

#====== an instantiation of scriptlog cipher key =========
$key = ScriptlogCryptonize::scriptlogCipherKey();

#====== an instantiation of scriptlog request path =======
$uri = new RequestPath();

// Register rules of routes, an instance of database connection, cipher key for cryptography and uri requested
Registry::setAll(array('dbc' => $dbc,  'key' => $key, 'route' => $rules, 'uri'=>$uri));

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
$sessionMaker = new SessionMaker(set_session_cookies_key($config['app']['email'], $config['app']['key']));
$searchPost = new SearchFinder();
$sanitizer = new Sanitize();
$userDao = new UserDao();
$userToken = new UserTokenDao();
$validator = new FormValidator();
$authenticator = new Authentication($userDao, $userToken, $validator);
$ubench = new Ubench();
$dispatcher = new Dispatcher();

session_set_save_handler($sessionMaker, true);
session_save_path(__DIR__ . '/utility/.sessions'.DS);
register_shutdown_function('session_write_close');

if (!start_session_on_site($sessionMaker)) {

    ob_start();

}

$errors = [];