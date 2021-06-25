<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class Dispatcher
 * 
 * @category Core Class
 * @author   M.Noermoehammad
 * @uses HandleRequest::allowedPathRequested
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
final class Dispatcher
{
  /**
   * route
   * 
   * @var mixed
   * 
   */
  private $route;
  
  /**
   * Theme's Directory
   *
   * @var string
   * 
   */
  private $theme_dir;
  
  /**
   * Constructor
   * Registry route and Initialize an instantiate of theme
   */
  public function __construct()
  {
    
    if ( Registry::isKeySet('route') ) {

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

    $this->theme_dir = APP_ROOT.APP_THEME.escape_html($this->invokeTheme()['theme_directory']).DS;

    if (false === HandleRequest::allowedPathRequested($this->whiteListPathRequested(), $this->route)) {

         $this->errorNotFound($this->theme_dir);

    } else {

      foreach ($this->route as $action => $routes) {
      
       if (preg_match( '~^'.$routes.'$~i', $this->requestURI(), $matches)) {
         
        http_response_code(200);
        call_theme_header(); 
        call_theme_content($action);
        call_theme_footer();

        exit();  // avoid the 404 message 
 
       } 
      
      }

    // nothing is found so handle the error page 404
    $this->errorNotFound($this->theme_dir);

    }
    
  }

  /**
   * RequestURI()
   * 
   * @return mixed
   * 
   */
  private function requestURI()
  {
    $script_name = rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/' );
    $request_uri = '/' . trim(str_replace( $script_name, '', $_SERVER['REQUEST_URI'] ), '/' );
    $uri_requested = urldecode( $request_uri );
    return $uri_requested;
  }

  /**
   * InvokeTheme
   * 
   * invoking theme actived
   * @return mixed
   * 
   */
  private function invokeTheme()
  {
    return theme_identifier();
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
    http_response_code(404);
    include($theme_dir.'404.php');
  }

  /**
   * whiteListPathRequested
   *
   * @return array
   * 
   */
  private function whiteListPathRequested()
  {
    return ['/', '//', 'post', 'page', 'blog', 'category', 'archive'];
  }

}