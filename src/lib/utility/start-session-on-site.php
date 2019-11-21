<?php
/**
 * 
 */
function start_session_on_site()
{
  $life_time = 600;

  $session_name = session_name();

  if (isset($_COOKIE[$session_name])) {

    $session_id = $_COOKIE[$session_name];

  } elseif (isset($_GET[$session_name])) {

     $session_id = $_GET[$session_name];

  } else {

     return session_start();

     return setcookie($session_name,session_id(),time()+$life_time);

  }

  if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $session_id)) {

     return false;

  }

  return session_start();

  return setcookie($session_name, session_id(), time()+$life_time);

}