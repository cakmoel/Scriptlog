<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class Dispatcher
 * 
 * @category Core Class
 * @author   M.Noermoehammad
 * @uses HandleRequest::allowedPathRequested if SEO-Friendly URL enabled otherwise then 
 * @uses HandleRequest::deliverQueryString when it disabled
 * @license  MIT
 * @see https://stackoverflow.com/questions/11696718/htaccess-rewrite-book-phpid-1234-to-book-1234
 * @see https://stackoverflow.com/questions/1039725/how-to-do-url-re-writing-in-php
 * @see https://stackoverflow.com/questions/60339372/how-to-rewrite-url-by-htaccess-with-basic-parameters-in-core-php
 * @see https://httpd.apache.org/docs/trunk/mod/mod_dir.html#fallbackresource
 * @see https://stackoverflow.com/questions/26419426/htaccess-url-re-styling-image-url-to-seo-friendly
 * @see https://mediatemple.net/community/products/dv/204643270/using-htaccess-rewrite-rules
 * @see https://stackoverflow.com/questions/16388959/url-rewriting-with-php
 * @version  1.1
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

    $theme = $this->invokeTheme();
    $this->theme_dir = APP_ROOT . APP_THEME . escape_html($theme['theme_directory']) . DIRECTORY_SEPARATOR;
  }

  /**
   * dispatch
   *
   */
  public function dispatch()
  {
    if (rewrite_status() === 'yes') {

      $this->handleSeoFriendlyUrl();
    } else {

      $this->handleQueryStringUrl();
    }
  }

  /**
   * handleSeoFriendlyUrl
   * 
   */
  public function handleSeoFriendlyUrl()
  {
    // The simplified allowedPathRequested now lets the request pass if the first segment is valid.
    if (!HandleRequest::allowedPathRequested($this->whiteListPathRequested(), $this->route)) {
      $this->errorNotFound();
      return;
    }

    $requestUri = $this->requestURI();

    // 1. Get RequestPath object from the Registry
    $requestPath = class_exists('Registry') ? Registry::get('uri') : null;

    foreach ($this->route as $key => $pattern) {

      $matches = []; // Initialize $matches as an empty array for safety

      // 2. Perform the regex match, capturing results into $matches
      if (preg_match('~^' . $pattern . '$~i', $requestUri, $matches)) {

        // Match found!

        // 3. CRITICAL FIX: Ensure $matches is an array and the object is valid before calling the setter
        if (is_object($requestPath) && method_exists($requestPath, 'setParameters') && is_array($matches)) {
          // Line 120 (approx): This call is now safe because we verified $matches is an array.
          $requestPath->setParameters($matches);
        }

        // 4. Render the found template
        $this->renderTheme($key);
        return;
      }
    }

    // If the loop finishes without a match, then it's a true 404.
    $this->errorNotFound();
  }

  /**
   * handleQueryStringUrl
   *
   */
  private function handleQueryStringUrl()
  {
    if (class_exists('HandleRequest')) {

      HandleRequest::deliverQueryString();
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
    // Get the path part of the REQUEST_URI, ignoring the query string
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    // Determine the base path (if installed in a subdirectory)
    $basePath = dirname($_SERVER['SCRIPT_NAME']);

    // Remove the base path from the request path if it exists
    if ($basePath !== '/' && str_starts_with($path, $basePath)) {
      $path = substr($path, strlen($basePath));
    }

    // Normalize: Ensure single leading slash and no trailing slash
    $path = '/' . trim($path, '/');

    // The resulting string should be exactly: /post/1/lorem-ipsum
    return urldecode($path);
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

  /**
   * renderTheme
   *
   * @param string $template
   * 
   */
  private function renderTheme($template)
  {
    http_response_code(200);
    call_theme_header();
    call_theme_content($template);
    call_theme_footer();
  }

  /* errorNotFound

  * set 404 error page
  * 
  * @param string $theme_dir
  * 
  */
  private function errorNotFound()
  {
    http_response_code(404);
    include $this->theme_dir . 'header.php';
    include $this->theme_dir . '404.php';
    include $this->theme_dir . 'footer.php';
  }

  /**
   * Get whitelisted paths
   */
  private function whiteListPathRequested()
  {
    return ['/', '//', 'post', 'page', 'blog', 'category', 'archive', 'tag'];
  }
}
