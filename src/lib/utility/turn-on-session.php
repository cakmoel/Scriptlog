<?php

/**
 * Turn on Function
 * Checking too old session ID and start session
 * 
 * @param number $life_time
 * @param string $session_name
 * @return void
 * 
 */
function turn_on_session($life_time, $session_name)
{
   
   session_start();
    
   // Do not allow to use too old session ID
   if (!empty($_SESSION['deleted_time']) && $_SESSION['deleted_time'] < time() - $life_time) {
        
      session_destroy();
        
      session_start();

      setcookie($session_name, session_id(), time()+$life_time);
 
   }

}

