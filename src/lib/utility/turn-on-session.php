<?php
/**
 * Turn on Function
 * Checking too old session ID and start session
 * 
 * @see https://github.com/GoogleChromeLabs/samesite-examples/blob/master/php.md 
 * @see https://stackoverflow.com/questions/36877/how-do-you-set-up-use-httponly-cookies-in-php
 * @see https://stackoverflow.com/a/46971326/2308553 
 * @param number $life_time
 * @param string $session_name
 * @return void
 * 
 */
function turn_on_session($life_time, $cookies_name, $path, $domain, $secure, $httponly)
{

   session_start();

   // Do not allow to use too old session ID
   if (!empty($_SESSION['deleted_time']) && $_SESSION['deleted_time'] < time() - $life_time) {
        
      session_unset();

      session_destroy();

      session_write_close();
        
      set_cookies_scl($cookies_name, session_id(), $life_time, $path, $domain, $secure, $httponly);

      session_regenerate_id(true);
     
   }

   session_canary();
   
}

/**
 * session_canary function
 * mitigate session fixation attacks
 * 
 * @see https://paragonie.com/blog/2015/04/fast-track-safe-and-secure-php-sessions
 * @return void
 * 
 */
function session_canary()
{

// Make sure we have a canary set
if (!isset($_SESSION['canary'])) {
   session_regenerate_id(true);
   $_SESSION['canary'] = time();
}
// Regenerate session ID every five minutes:
if ($_SESSION['canary'] < time() - 300) {
   session_regenerate_id(true);
   $_SESSION['canary'] = time();
}

}
