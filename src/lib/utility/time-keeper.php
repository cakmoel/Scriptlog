<?php
/**
 * time_keeper
 * 
 * @category function
 * @version 1.0
 * @license MIT
 * 
 */
function time_keeper()
{
    $time_limit = 10440;
    $_SESSION ['timeOut'] = time() + $time_limit;
}

/**
 * validate_time_login
 * 
 * @category function
 * @version 1.0
 * @license MIT
 * @return boolean
 */
function validate_time_login()
{
    
  $timeOut = $_SESSION['timeOut'];
    
  if (time() < $timeOut) {
        
     time_keeper();
     return true;
    
  } else {
        
      unset($_SESSION['timeOut']);
        
      return false;
      
  }
    
}