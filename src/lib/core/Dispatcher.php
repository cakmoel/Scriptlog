<?php
/**
 * Class Dispatcher
 * 
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @see https://stackoverflow.com/questions/11696718/htaccess-rewrite-book-phpid-1234-to-book-1234
 * @see https://stackoverflow.com/questions/1039725/how-to-do-url-re-writing-in-php
 * @see https://stackoverflow.com/questions/60339372/how-to-rewrite-url-by-htaccess-with-basic-parameters-in-core-php
 * @see https://httpd.apache.org/docs/trunk/mod/mod_dir.html#fallbackresource
 * @see https://stackoverflow.com/questions/26419426/htaccess-url-re-styling-image-url-to-seo-friendly
 * @see https://mediatemple.net/community/products/dv/204643270/using-htaccess-rewrite-rules
 * @see https://stackoverflow.com/questions/16388959/url-rewriting-with-php
 * @version  1.0
 * @since    Since Release 0.1
 * 
 */
class Dispatcher
{
  /**
   * routes 
   * 
   * @var object
   * 
   */
  private static $route;

  /**
   * errors
   * 
   * @var string
   * 
   */
  private $errors;

  /**
   * Constructor
   * Registry route and Initialize an instantiate of theme
   */
  public function __construct()
  {
     if (Registry::isKeySet('route')) {

      self::$route = Registry::get('route');

     }

  }

  /**
   * Dispacth route requested by rules
   * and identify where should respond it in active theme
   * 
   */
  public function dispatch()
  {

    if (!$this->invokeTheme()) {
        
        include(APP_ROOT.APP_THEME.'themes'.DS.'maintenance.php');
      
    } else {

      $theme_dir = APP_ROOT.APP_THEME.safe_html($this->invokeTheme()['theme_directory']).DS;

      if (false === self::allowedPath(self::whiteListPathRequested())) {

        // nothing is found so handle the error page 404
        $this->errorNotFound($theme_dir);

      } else {

        foreach (self::$route as $action => $routes) {
        
          if (preg_match( '~^'.$routes.'$~i', self::requestURI(), $matches)) {
             
              if (is_dir($theme_dir)) {

                 call_theme_header(); 
                 call_theme_content($action);
                 call_theme_footer();
                
              }
  
             exit();  // avoid the 404 message 
     
          } 
     
        }
  
        // nothing is found so handle the error page 404
        $this->errorNotFound($theme_dir);
  
      }
      
    }
    
  }

  /**
   * Find route defined by rules
   * 
   * @return mixed
   * 
   */
  public function findRules()
  {
    $keys = array();
    $values = array();
  
    foreach (self::$route as $key => $value) {
      
      $keys[] = $key; 
      $values[] = $value;    
      
   }
  
   return array("keys" => $keys, "values" => $values);
  
  }

  /**
   * Find request path
   * 
   * @param int $args
   * @return mixed if true $var path return array
   * @return bool|false if $var path does not return path
   * 
   */
  public static function findRequestPath($args)
  {
    
    $path = explode('/', self::requestPath());

    if (isset($path[$args])) {

      return basename($path[$args]);

    } else {
       
       return false;

    }
    
  }

  /**
   * Find Request Parameters
   * 
   * @return mixed
   * 
   */
  public static function findRequestParam()
  {
    
    $parameters = [];

    if (is_array(self::$route)) {

      foreach (self::$route as $key => $value) {
      
        if (preg_match('~^'.$value.'$~i', self::requestURI(), $matches)) {
 
          return $parameters[] = $matches;
 
        }
 
     }

    }
    
  }

  /**
   * parseQuery from URL requested
   * 
   * @return mixed
   * 
   */
  public function parseQuery($var)
  {
    $var  = parse_url($var, PHP_URL_QUERY);
    $var  = html_entity_decode($var);
    $var  = explode('&', $var);
    $queries  = array();
    
    foreach($var as $val) {

      $x = explode('=', $val);
      $queries[$x[0]] = $x[1];

    }
    
    unset($val, $x, $var);
    
    return $queries;
    
  }

  /**
   * RequestPath
   * 
   * @return mixed;
   * 
   */
  private static function requestPath()
  {
    
    $request_uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $script_name = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
    $parts = array_diff_assoc($request_uri, $script_name);
     
    if (empty($parts)) {

      return '/';
      
    }
     
    $path = implode('/', $parts);
   
    if (($position = strpos($path, '?')) !== FALSE) {

     $path = substr($path, 0, $position);

    }
     
    return $path;
   
  }

  /**
   * Request URI
   * 
   * @return mixed
   * 
   */
  private static function requestURI()
  {
    $uri = rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/' );
    $uri = '/' . trim(str_replace( $uri, '', $_SERVER['REQUEST_URI'] ), '/' );
    $uri = urldecode( $uri );
    return $uri;
  }

  /**
   * Grab active theme
   */
  private function invokeTheme()
  {
    return theme_identifier();
  }
  
  /**
   * allowed path
   * Checking whether URI requested match the rules and allowed to be executed
   * 
   * @param string $theme_dir
   * @param array $path
   * @return bool|true|false
   * 
   */
  private static function allowedPath(array $path)
  {
    
    $findParam = self::findRequestParam();
    
    $param1 = (is_array($findParam) && array_key_exists(0, $findParam)) ? $findParam[0] : '';
    
    if (!(in_array(self::findRequestPath(0), $path, true) || (in_array($param1, $path, true)))) {

      return false; 
       
    } else {

      return true;

    }

  }

  /**
   * Error not found 404
   * set 404 error page
   * 
   * @param string $theme_dir
   * 
   */
  private function errorNotFound($theme_dir)
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
    include($theme_dir.'404.php');
  }

/**
 * whiteListPathRequested
 * whitelist allowed path that being requested on the common visitor-side (not admin dir)
 * 
 * @return array
 * 
 */
  private static function whiteListPathRequested()
  {
    return ['/', '//', 'post', 'page', 'blog', 'category', 'archive'];
  }

} // End of class Dispatcher