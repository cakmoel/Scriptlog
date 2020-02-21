<?php
/**
 * Function start session on site
 * 
 * @category function 
 * 
 */
function start_session_on_site()
{
  $life_time = time() + Authentication::COOKIE_EXPIRE;

  $session_name = session_name();
  
  if (isset($_COOKIE[$session_name])) {

   $session_id = $_COOKIE[$session_name];

 } elseif (isset($_GET[$session_name])) {

    $session_id = $_GET[$session_name];

 } else {

   return turn_on_session($life_time, $session_name);
   
 }

 if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $session_id)) {

   return false;

} 

return turn_on_session($life_time, $session_name);

}

