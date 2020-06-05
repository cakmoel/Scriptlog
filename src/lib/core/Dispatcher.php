<?php
/**
 * Class Dispatcher
 * 
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 0.1
 * 
 */
class Dispatcher
{
  /**
   * routes 
   * 
   * @var string
   * 
   */
  private $route;

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

        $this->route = Registry::get('route');

     }

  }

  /**
   * Dispacth route requested by rules
   * and identify where should respond it in active theme
   * 
   */
  public function dispatch()
  {

    if (!$themeActived = $this->invokeTheme()) {
        
        include(APP_ROOT.APP_PUBLIC.DS.'themes'.DS.'maintenance.php');
      
    } else {

      $theme_dir = APP_ROOT.APP_PUBLIC.DS.$themeActived['theme_directory'].DS;

      if (false === $this->allowedPath(['/', '//', 'post', 'page', 'blog', 'category', 'contact'])) {

        // nothing is found so handle the error page 404
        $this->errorNotFound($theme_dir);

      } else {

        foreach ($this->route as $action => $routes) {
        
          if (preg_match( '~^'.$routes.'$~i', $this->requestURI(), $params)) {
             
              if (is_dir($theme_dir)) {
  
                 call_theme_header();
                 call_theme_content($action);
                 call_theme_footer();

              }
  
             // avoid the 404 message 
             exit();
     
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
  
    foreach ($this->route as $key => $value) {
      
      $keys[] = $key; 
      $values[] = $value;    
      
   }
  
   return array("keys" => $keys, "values" => $values);
  
  }

  /**
   * Find request path
   * 
   * @param array $args
   * @return mixed if true $var path return array
   * @return false if $var path does not return path
   * 
   */
  public function findRequestPath($args)
  {
    $path = $this->requestPath();
    $path = explode('/', $path);

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
  public function findRequestParam()
  {
    
    $parameters = [];

    foreach ($this->route as $key => $value) {
      
       if (preg_match('~^'.$value.'$~i', $this->requestURI(), $matches)) {

         return $parameters[] = $matches;

       }

    }

  }

  /**
   * Parse query from URL requested
   * 
   * @return mixed
   * 
   */
  public function parseQuery()
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
   * Request path
   * 
   * @return mixed;
   * 
   */
  protected function requestPath()
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
  protected function requestURI()
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
  private function allowedPath(array $path)
  {
    
    $findParam = $this->findRequestParam();
    
    $param1 = (is_array($findParam) && array_key_exists(0, $findParam)) ? $findParam[0] : '';
    
    if (!(in_array($this->findRequestPath(0), $path, true) || (in_array($param1, $path, true)))) {

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

} // End of class Dispatcher