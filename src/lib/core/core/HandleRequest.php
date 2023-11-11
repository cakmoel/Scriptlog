<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * final class HandleRequest
 * 
 * this class used by Dispatcher to check allowed path requested
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */
final class HandleRequest
{

/**
 * requestPathURI
 *
 * @var object
 * 
 */
private static $requestPathURI;

/**
 * frontHelper
 *
 * @var object
 * 
 */
private static $frontHelper;

/**
 * handleFrontHelper
 *
 * @return object
 * 
 */
public static function handleFrontHelper()
{
  self::$frontHelper = class_exists('FrontHelper') ? new FrontHelper() : '';
  return self::$frontHelper;
}

/**
 * findRequestToRules
 *
 * @param array $rules
 * @return array
 * 
 */
private static function findRequestToRules($rules)
{

$script_name = isset($_SERVER['SCRIPT_NAME']) ? rtrim(dirname($_SERVER['SCRIPT_NAME']), '/' ) : '';
$request_uri = '/' . trim(str_replace($script_name, '', $_SERVER['REQUEST_URI']), '/');
$uri = urldecode($request_uri);

$parameters = [];

if (is_array($rules)) {

    foreach ($rules as $key => $value) {
      
      if (preg_match('~^'.$value.'$~i', $uri, $matches)) {
 
        return $parameters[] = $matches;
      }
 
    }

}

}

/**
 * findRequestToPath()
 *
 * @return string
 * 
 */
private static function findRequestToPath()
{
 $request_uri = isset($_SERVER['REQUEST_URI']) ? explode('/', trim(escape_html($_SERVER['REQUEST_URI']), '/')) : null;
 $script_name = isset($_SERVER['SCRIPT_NAME']) ? explode('/', trim(escape_html($_SERVER['SCRIPT_NAME']), '/')) : null;
 $parts = array_diff_assoc($request_uri, $script_name);
     
 if (empty($parts)) {
    
  return '/';
      
 }
     
 $path = implode('/', $parts);
    
 if (($position = strpos($path, '?')) !== false) {
    
  $path = substr($path, 0, $position);
    
 }

 return $path;
 
}

/**
 * isRequestToPathValid()
 *
 * @param int $args
 * @uses HandleRequest::findRequestPath 
 * @return string|boolean return string if return true otherwise will return false
 * 
 */
private static function isRequestToPathValid($args)
{

 $path = explode('/', self::findRequestToPath());

 if (isset($path[$args])) {

    return basename($path[$args]);

  } else {
       
    return false;

  }

}

/**
 * isMatchedUriRequested()
 *
 * @return mixed
 * 
 */
public static function isMatchedUriRequested()
{
  $matched_uri = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], DIRECTORY_SEPARATOR) : "";
  $slice_matched = explode(DIRECTORY_SEPARATOR, $matched_uri);
  return isset($slice_matched[0]) ? $slice_matched[0] : "";
}

/**
 * isQueryStringRequested()
 *
 * @return mixed
 * 
 */
public static function isQueryStringRequested()
{
  $string_requested = isset($_SERVER['QUERY_STRING']) ? escape_html($_SERVER['QUERY_STRING']) : null;
  $slice_query = explode('=', $string_requested);
  $get_key = isset($slice_query[0]) ? $slice_query[0] : "";
  $get_value = isset($slice_query[1]) ? $slice_query[1] : "";
  return array('key' => $get_key, 'value'=>$get_value);
}

/**
 * checkMatchUriRequested()
 *
 * @return bool
 * 
 */
public static function checkMatchUriRequested()
{
  self::$requestPathURI = class_exists('RequestPath') ? new RequestPath() : '';
  return (self::isMatchedUriRequested() === self::$requestPathURI->matched) ? true : false;
}

/**
 * allowedPathRequested
 * 
 * Checking whether URI requested match the rules and allowed to be executed 
 */
public static function allowedPathRequested($path, $rules)
{

 $rule_requested = self::findRequestToRules($rules);

 $is_valid_requested = (is_array($rule_requested) && array_key_exists(0, $rule_requested)) ? $rule_requested[0] : null;

 return (!(in_array(self::isRequestToPathValid(0), $path, true) || (in_array($is_valid_requested, $path, true)))) ? false : true;

}

/**
 * deliverQueryString
 *
 */
public static function deliverQueryString()
{

  switch (static::isQueryStringRequested()['key']) {

    case 'p':

      // Deliver request to a single post entry
      if (! empty(static::isQueryStringRequested()['value'])) {

        $query_post = self::handleFrontHelper()->grabSimpleFrontPost(static::isQueryStringRequested()['value']);

        if (empty($query_post['ID'])) {

          http_response_code(404);
          call_theme_header();
          call_theme_content('404');
          call_theme_footer();

        } else {

          http_response_code(200);
          call_theme_header();
          call_theme_content('single');
          call_theme_footer();

        }
      
      } else {

        direct_page('', 302);

      }

      break;
    
    case 'cat':
      // Deliver request to a single category or topic
      if (! empty(static::isQueryStringRequested()['value'])) {

        $query_cat = self::handleFrontHelper()->grabSimpleFrontTopic(static::isQueryStringRequested()['value']);

        if (empty($query_cat['ID'])) {

           http_response_code(404);
           call_theme_header();
           call_theme_content('404');
           call_theme_footer();

        } else {

          http_response_code(200);
          call_theme_header();
          call_theme_content('category');
          call_theme_footer();

        }
        
      } else {

        direct_page('', 302);

      }

      break;

    case 'pg':
      // Deliver request to a single page
      if (! empty(static::isQueryStringRequested()['value'])) {

        $query_page = self::handleFrontHelper()->grabSimpleFrontPage(static::isQueryStringRequested()['value']);

        if (empty($query_page['ID'])) {

          http_response_code(404);
          call_theme_header();
          call_theme_content('404');
          call_theme_footer();

        } else {

          http_response_code(200);
          call_theme_header();
          call_theme_content('page');
          call_theme_footer();

        }
       
      } else {

        direct_page('', 302);

      }

      break;

    case 'a':

      // Deliver request to an archives
      if (! empty(static::isQueryStringRequested()['value'])) {

        http_response_code(200);
        call_theme_header();
        call_theme_content('archive');
        call_theme_footer();

      } else {

        direct_page('', 302);

      }
      
      break;

    case 'tag':
      
        // Deliver request to a tag
      if (! empty(static::isQueryStringRequested()['value'])) {

          $query_tags = self::handleFrontHelper()->grabFrontTag(static::isQueryStringRequested()['value']);

          if (is_iterable($query_tags)) {

            foreach ($query_tags as $tag) {

              $post_id = isset($tag['ID']) ? escape_html($tag['ID']) : 0;
              
            }

            if (empty($post_id)) {

              http_response_code(404);
              call_theme_header();
              call_theme_content('404');
              call_theme_footer();
    
            } else {

              http_response_code(200);
              call_theme_header();
              call_theme_content('tag');
              call_theme_footer();

            }
            
          } 

      } else {

        direct_page('', 302);

      }

      break;

    case 'blog':
      // Deliver request to blog
      http_response_code(200);
      call_theme_header();
      call_theme_content('blog');
      call_theme_footer();

      break;

    default:  # default request will be delivered
     
      if (false === static::checkMatchUriRequested()) {

        direct_page('', 500);

      } else {

        http_response_code(200);
        call_theme_header();
        call_theme_content('home');
        call_theme_footer();

      }
      
      break;

  }
  
}

}