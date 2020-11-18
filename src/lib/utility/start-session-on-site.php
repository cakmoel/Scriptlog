<?php
/**
 * start_session_on_site
 * 
 * @category function 
 * @author M.Noermoehammad
 * @license MIT
 * @see https://www.php.net/manual/en/ref.session.php
 * @see https://github.com/GoogleChromeLabs/samesite-examples/blob/master/php.md 
 * @see https://stackoverflow.com/questions/36877/how-do-you-set-up-use-httponly-cookies-in-php
 * @see https://stackoverflow.com/a/46971326/2308553 
 * @uses turn_on_session() functions
 * @return mixed
 * 
 */
function start_session_on_site($session_handler)
{
  
  $life_time = Authentication::COOKIE_EXPIRE;

  $session_name = session_name();
  
 if(ini_get('session.use_cookies')) {

    $current_cookie_params = session_get_cookie_params();

 }

 $_SESSION['deleted_time'] = time();

 if (isset($_COOKIE[$session_name])) {

   $session_id = $_COOKIE[$session_name];

 } elseif (isset($_GET[$session_name])) {

    $session_id = $_GET[$session_name];

 } else {

   return turn_on_session($session_handler, $life_time, $session_name, $current_cookie_params["path"], $current_cookie_params["domain"], $current_cookie_params["secure"], true);
   
 }

 if(!session_valid_id($session_id)) {

   return false;

 }

return turn_on_session($session_handler, $life_time, $session_name, $current_cookie_params["path"], $current_cookie_params["domain"], $current_cookie_params["secure"], true);

}

/**
 * session_valid_id
 * 
 * @category function
 * @license MIT
 * @version 1.0
 * @see https://www.php.net/manual/en/function.session-id.php
 * @see https://akrabat.com/validating-default-php-session-id-values/
 * @param string $session_id
 * @return void
 * 
 */
function session_valid_id($session_id)
{

  if (is_string($session_id)) {

    if (PHP_VERSION_ID >= 70100) {
  
      $sidLength = ini_get('session.sid_length');
       
        switch (ini_get('session.sid_bits_per_character')) {
            case 6:
                $characterClass = '0-9a-zA-z,-';
                break;
            case 5:
                $characterClass = '0-9a-v';
                break;
            case 4:
                $characterClass = '0-9a-f';
                break;
            default:
                throw new \RuntimeException('Unknown value in session.sid_bits_per_character.');
        }
      
        $pattern = '/^[' . $characterClass . ']{' . $sidLength . '}$/';
    
        return preg_match($pattern, $session_id) === 1;
       
    } else {
    
      return preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $session_id) > 0;
    
    }
    
  }
  
}
