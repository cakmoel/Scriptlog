<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class HandleRequest
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

}