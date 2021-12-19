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
 * findRequestToRules
 *
 * @param array $rules
 * @return array
 * 
 */
private static function findRequestToRules(array $rules)
{

$script_name = rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/' );
$request_uri = '/' . trim(str_replace( $script_name, '', $_SERVER['REQUEST_URI'] ), '/' );
$uri = urldecode( $request_uri );

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
 * @return mixed
 * 
 */
private static function findRequestToPath()
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
 * isRequestToPathValid()
 *
 * @param int $args
 * @uses HandleRequest::findRequestPath 
 * @return boolean
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
  $matched_uri = (isset($_SERVER['REQUEST_URI'])) ? trim($_SERVER['REQUEST_URI'], DIRECTORY_SEPARATOR) : "";
  $slice_matched = explode(DIRECTORY_SEPARATOR, $matched_uri);
  $get_matched = isset($slice_matched[0]) ? $slice_matched[0] : "";
  return $get_matched;
}

/**
 * isQueryStringRequested()
 *
 * @return mixed
 * 
 */
public static function isQueryStringRequested()
{
  $query_string = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : "";
  $slice_query = explode('=', $query_string);
  $get_key = isset($slice_query[0]) ? $slice_query[0] : "";
  $get_value = isset($slice_query[1]) ? $slice_query[1] : "";
  return array('key' => $get_key, 'value'=>$get_value);
}

/**
 * allowedPathRequested
 * 
 * Checking whether URI requested match the rules and allowed to be executed
 *
 * @param array $path
 * @param array $rules
 * @return bool
 * 
 */
public static function allowedPathRequested(array $path, array $rules)
{

 $rule_requested = self::findRequestToRules($rules);

 $is_valid_requested = ( is_array($rule_requested) && array_key_exists(0, $rule_requested) ) ? $rule_requested[0] : null;

 if ( ! (in_array(self::isRequestToPathValid(0), $path, true) || (in_array($is_valid_requested, $path, true ) ) ) ) {

  return false;

 } else {

  return true;

 }

}

/**
 * checkMatchUriRequested()
 *
 * @return bool
 * 
 */
public static function checkMatchUriRequested()
{
  
  self::$requestPathURI = new RequestPath();

  if ( self::isMatchedUriRequested() === self::$requestPathURI->matched ) {
    
    return true;

  } else {

    return false;

  }

}

}