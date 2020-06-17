<?php
/**
 * Turn on Function
 * Checking too old session ID and start session
 * 
 * @see https://github.com/GoogleChromeLabs/samesite-examples/blob/master/php.md 
 * @see https://stackoverflow.com/a/46971326/2308553 
 * @param number $life_time
 * @param string $session_name
 * @return void
 * 
 */
function turn_on_session($life_time, $cookies_name, $path, $domain, $secure, $httponly)
{
   
   $cookies_value = session_id(ircmaxell_random_compat());

   session_start();
    
   // Do not allow to use too old session ID
   if (!empty($_SESSION['deleted_time']) && $_SESSION['deleted_time'] < time() - $life_time) {
        
      session_destroy();
        
      session_start();

      set_cookies_scl($cookies_name, $cookies_value, $life_time, $path, $domain, $secure, $httponly);
     
   }

}
