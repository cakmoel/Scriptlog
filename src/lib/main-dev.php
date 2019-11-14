<?php
/**
 * main-dev.php
 * Initialize main engine, define constants, and object instantiated
 * include functions needed by application
 * 
 * @package  SCRIPTLOG
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * 
 */

# date_default_timezone_set("GMT");
ini_set("memory_limit", "1M");
# ini_set("session.cookie_secure", "True");  //secure
# ini_set("session.cookie_httponly", "True"); // httpOnly
# header("Content-Security-Policy: default-src https:; font-src 'unsafe-inline' data: https:; form-action 'self' https://kartatopia.com;img-src data: https:; child-src https:; object-src 'self' www.google-analytics.com ajax.googleapis.com platform-api.sharethis.com kartatopia-studio.disqus.com; script-src 'unsafe-inline' https:; style-src 'unsafe-inline' https:;");

$key = '5c12IpTl0g!@#';
$checkIncKey = sha1(mt_rand(1, 1000).$key);

define('DS', DIRECTORY_SEPARATOR);
define('APP_TITLE', 'Scriptlog');
define('APP_CODENAME', 'Maleo Senkawor');
define('APP_VERSION', '1.0');
define('APP_ADMIN', 'admin');
define('APP_PUBLIC', 'public');
define('APP_LIBRARY', 'lib');
define('APP_DEVELOPMENT', true);
define('APP_CACHE', true);
define('SCRIPTLOG', $checkIncKey);

if (!defined('APP_ROOT')) define('APP_ROOT', dirname(dirname(__FILE__)) . DS);

if (!defined('PHP_EOL')) define('PHP_EOL', strtoupper(substr(PHP_OS, 0, 3) == 'WIN') ? "\r\n" : "\n");

if (!defined('APP_PROTOCOL')) define('APP_PROTOCOL', strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === false ? 'http' : 'https');

if (!defined('APP_HOSTNAME')) define('APP_HOSTNAME', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);

if (true === APP_DEVELOPMENT) {

    if (!defined('SCRIPTLOG_START_TIME')) define('SCRIPTLOG_START_TIME', microtime(true));

    if (!defined('SCRIPTLOG_START_MEMORY')) define('SCRIPTLOG_START_MEMORY', memory_get_usage());

}

if (file_exists(APP_ROOT . 'config.sample.php')) {

    $config = require __DIR__ . '/../config.sample.php';

} 

// call functions in folder utility
$function_directory = new RecursiveDirectoryIterator(__DIR__ . DS .'utility'. DS, FilesystemIterator::FOLLOW_SYMLINKS);
$filter_iterator = new RecursiveCallbackFilterIterator($function_directory, function ($current, $key, $iterator){
    
    // skip hidden files and directories
    if ($current->getFilename()[0] === '.') {
        return false;
    }
    
    if ($current->isDir()) {
        
        // only recurse into intended subdirectories
        return $current->getFilename() === __DIR__ . DS .'utility'. DS;
        
    } else {
        
        // only consume files of interest
        return strpos($current -> getFilename(), '.php');
        
    }
    
});
        
$files_dir_iterator = new RecursiveIteratorIterator($filter_iterator);
    
foreach ($files_dir_iterator as $file) {
        
   include($file -> getPathname());
        
}
    
if (is_dir(APP_ROOT . APP_LIBRARY) && is_file(APP_ROOT . APP_LIBRARY . DS . 'Scriptloader.php')) {
    
    require __DIR__ . DS . 'Scriptloader.php';
    
}

// load all libraries 
$library = array(
    APP_ROOT . APP_LIBRARY . DS .'core'. DS,
    APP_ROOT . APP_LIBRARY . DS .'dao'. DS,
    APP_ROOT . APP_LIBRARY . DS .'event'. DS,
    APP_ROOT . APP_LIBRARY . DS .'app'. DS,
    APP_ROOT . APP_LIBRARY . DS .'plugins'. DS
);

load_engine($library);

#===================== RULES ===========================

// rules used by dispatcher to route request

/*******************************************************  

     ### '/picture/some-text/51' 
    'picture' => "/picture/(?'text'[^/]+)/(?'id'\d+)",    
    
     ### '/album/album-slug'
    'album' => "/album/(?'album'[\w\-]+)",              
    
     ### '/category/category-slug'
    'category' => "/category/(?'category'[\w\-]+)",        
    
     ### '/blog?p=255'
    'blog' => "/blog([^/]*)",                       
    
     ### '/page/about', '/page/contact'
    'page' => "/page/(?'page'[^/]+)
     
    ### '/post/60/post-slug'
    'post' => "/post/(?'id'\d+)/(?'post'[\w\-]+)",     
    
     ### '/'
    'home' => "/"                                        

 *******************************************************/

$rules = array(
    'home'     => "/",                               
    'category' => "/category/(?'category'[\w\-]+)",
    'blog'     => "/blog([^/]*)",
    'page'     => "/page/(?'page'[^/]+)",
    'single'   => "/post/(?'id'\d+)/(?'post'[\w\-]+)",
    'search'   => "(?'search'[\w\-]+)"
);

#==================== END OF RULES ======================

// an instantiation of Database connection
$dbc = DbFactory::connect(['mysql:host='.$config['db']['host'].';dbname='.$config['db']['name'],
    $config['db']['user'], $config['db']['pass']
]);

// Register rules and an instance of database connection
Registry::setAll(array('dbc' => $dbc, 'route' => $rules));

/* an instances of class that necessary for the system
 * please do not change this below variable 
 * 
 * @var $searchPost used by search functionality
 * @var $frontPaginator used by front pagination funtionality
 * @var $postFeeds used by rss feed functionality
 * @var $sanitizer used by sanitize functionality
 * @var $userDao, $validator, $authenticator --
 * these are collection of objects or instances of classes 
 * that will be run by the system
 * 
 */
$searchPost = new SearchFinder($dbc);
$frontPaginator = new Paginator(10, 'p');
$postFeeds = new RssFeed($dbc);
$sanitizer = new Sanitize();
$userDao = new User();
$userToken = new UserToken();
$validator = new FormValidator();
$authenticator = new Authentication($userDao, $userToken, $validator);

// These line (175 and 176) are experimental code. You do not need it.
# $bones = new Bones();
# $request = new RequestHandler($bones);

# set_exception_handler('LogError::exceptionHandler');
# set_error_handler('LogError::errorHandler');
# register_shutdown_function('scriptlog_shutdown_fatal');

if (!isset($_SESSION)) {

    session_start();
    
}

ob_start();