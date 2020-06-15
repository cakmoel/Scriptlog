<?php
/**
 * Function start session on site
 * 
 * @see https://github.com/GoogleChromeLabs/samesite-examples/blob/master/php.md 
 * @see https://stackoverflow.com/a/46971326/2308553 
 * @category function 
 * @uses turn_on_session()
 * @return mixed
 * 
 */
function start_session_on_site()
{
  
  $life_time = time() + Authentication::COOKIE_EXPIRE;

  $current_cookie_params = session_get_cookie_params();

  if (PHP_VERSION_ID >= 70300) { 
      
      session_set_cookie_params([
        'lifetime' => $life_time,
        'path' => '/',
        'domain' => $current_cookie_params["domain"],
        'secure' => $current_cookie_params["secure"],
        'httponly' => $current_cookie_params["httponly"],
        'samesite' => 'Lax'
      ]);

    } else { 

      session_set_cookie_params(
        $life_time,
        '/; samesite=Lax',
        $current_cookie_params["domain"],
        $current_cookie_params["secure"],
        $current_cookie_params["httponly"]
      );

    }

  $session_name = session_name('scl');
  
  if (isset($_COOKIE[$session_name])) {

   $session_id = $_COOKIE[$session_name];

 } elseif (isset($_GET[$session_name])) {

    $session_id = $_GET[$session_name];

 } else {

   return turn_on_session($life_time, $session_name, $current_cookie_params["path"], $current_cookie_params["domain"], $current_cookie_params["secure"], $current_cookie_params["httponly"]);
   
 }

 if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $session_id)) {

   return false;

} 

return turn_on_session($life_time, $session_name, $current_cookie_params["path"], $current_cookie_params["domain"], $current_cookie_params["secure"], $current_cookie_params["httponly"]);

}

