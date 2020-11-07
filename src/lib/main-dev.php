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
ini_set('memory_limit', "5M");
error_reporting(-1);
#ini_set("session.cookie_secure", 1);  
#ini_set("session.cookie_lifetime", 86400);  
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

if (!defined('PHP_EOL')) {

  if (strtoupper(substr(PHP_OS,0,3) == 'WIN')) {
        
    define('PHP_EOL',"\r\n");

  } elseif (strtoupper(substr(PHP_OS,0,3) == 'MAC')) {
        
    define('PHP_EOL',"\r");
    
  } elseif (strtoupper(substr(PHP_OS,0,3) == 'DAR')) {
        
    define('PHP_EOL',"\n");
      
  } else {
        
    define('PHP_EOL',"\n");
      
  }

} 

$is_secure = false;

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {

 $is_secure = true;

} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
    
 $is_secure = true;
    
}

if (!defined('APP_PROTOCOL')) define('APP_PROTOCOL', $protocol = $is_secure ? 'https' : 'http');

if (!defined('APP_HOSTNAME')) define('APP_HOSTNAME', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);

$config = null;

if (is_readable(APP_ROOT.'config.php')) {
    
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
if (is_dir(APP_ROOT . APP_LIBRARY) && is_file(APP_ROOT . APP_LIBRARY . DS . 'Scriptloader.php')) {
 
    require __DIR__ . DS . 'Scriptloader.php';
      
}

if (is_readable(APP_ROOT.APP_LIBRARY.DS.'vendor/autoload.php')) {

    require __DIR__ . DS . 'vendor/autoload.php';
    
}

// load all libraries 
$library = array(
    APP_ROOT . APP_LIBRARY . DS . 'core'    . DS,
    APP_ROOT . APP_LIBRARY . DS . 'dao'     . DS,
    APP_ROOT . APP_LIBRARY . DS . 'event'   . DS,
    APP_ROOT . APP_LIBRARY . DS . 'app'     . DS,
    APP_ROOT . APP_ADMIN   . DS . 'plugins' . DS
);

get_server_load();

load_engine($library);

call_htmlpurifier();

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

#==================== END OF RULES ======================

#====== an instantiation of Database connection =========
$dbc = DbFactory::connect(['mysql:host='.$config['db']['host'].';dbname='.$config['db']['name'], $config['db']['user'], $config['db']['pass']]);

$key = ScriptlogCryptonize::scriptlogCipherKey();

// Register rules of routes, an instance of database connection and key for decryption message
Registry::setAll(array('dbc' => $dbc, 'route' => $rules, 'key' => $key));

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
$searchPost = new SearchFinder($dbc);
$sanitizer = new Sanitize();
$userDao = new UserDao();
$userToken = new UserTokenDao();
$postDao = new PostDao();
$topicDao = new TopicDao();
$postTopicDao = new PostTopicDao();
$pageDao = new PageDao();
$menuDao = new MenuDao();
$frontDispatcher = new Dispatcher();
$validator = new FormValidator();
$authenticator = new Authentication($userDao, $userToken, $validator);
$ubench = new Ubench();
$sessionMaker = new SessionMaker(set_session_cookies_key());

content_security_policy();

session_set_save_handler($sessionMaker, true);
session_save_path(__DIR__ . '/utility/.sessions'.DS);

# set_exception_handler('LogError::exceptionHandler');
# set_error_handler('LogError::errorHandler');
# register_shutdown_function('scriptlog_shutdown_fatal');

if (!start_session_on_site($sessionMaker)) {

    ob_start();
    
}

$errors = [];