<?php
/**
 * user_privilege()
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
function user_privilege()
{
    
 if (isset($_COOKIE['scriptlog_auth'])) {
  
   Authorization::setAuthInstance(new Authentication(new UserDao, new UserTokenDao, new FormValidator));
          
   return Authorization::authorizeLevel();
  
 }
  
 if (isset(Session::getInstance()->scriptlog_session_level)) {
  
   return Session::getInstance()->scriptlog_session_level;
      
 }

 return false;
 
}