<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class Dispatcher
 * 
 * @category Core Class
 * @author   M.Noermoehammad
 * @uses HandleRequest::allowedPathRequested if rewrite url enabled otherwise then 
 * @uses HandleRequest::deliverQueryString when it disabled
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
   * route
   * 
   * @var mixed
   * 
   */
  private $route = [];

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

    $this->theme_dir = APP_ROOT.APP_THEME.escape_html($this->invokeTheme()['theme_directory']).DIRECTORY_SEPARATOR;

    if (rewrite_status() === 'yes') {

      if (false === HandleRequest::allowedPathRequested($this->whiteListPathRequested(), $this->route)) {

        // nothing is found so handle the error page 404
        $this->errorNotFound($this->theme_dir);

      } else {
  
        foreach ($this->route as $key => $value) {
  
          // Add delimiters to the regex pattern
          $pattern = '~^' . $value . '$~i';

          if (preg_match($pattern, $this->requestURI(), $matches)) {
           
            http_response_code(200);
            
            call_theme_header(); 
            call_theme_content($key);
            call_theme_footer();
            
            return;

          } 

        }

        direct_page('404.php', 404);  

      }

    } else {

      (class_exists('HandleRequest')) ? HandleRequest::deliverQueryString() : "";
      
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
    $script_name = rtrim(dirname($_SERVER["SCRIPT_NAME"]), DIRECTORY_SEPARATOR);
    $request_uri = DIRECTORY_SEPARATOR . trim(str_replace($script_name, '', $_SERVER['REQUEST_URI']), DIRECTORY_SEPARATOR);
    return urldecode($request_uri);
  }

  /**
   * whiteListPathRequested
   *
   * @return array
   * 
   */
  private function whiteListPathRequested()
  {
    return ['/', '//', 'post', 'page', 'blog', 'category', 'archive', 'tag'];
  }

  /* InvokeTheme
   * 
   * invoking theme actived
   * @return mixed
   * 
   */
  private function invokeTheme()
  {
    return theme_identifier();
  }

  /* Error not found 404
  * set 404 error page
  * 
  * @param string $theme_dir
  * 
  */
  private function errorNotFound($theme_dir)
  {
    http_response_code(404);
    include $theme_dir . 'header.php';
    include $theme_dir . '404.php';
    include $theme_dir . 'footer.php';
  }
}
