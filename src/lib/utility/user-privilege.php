<?php
/**
 * user_privilege()
 *
 * @category function
 * @author M.Noermoehammad <scriptlog@yandex.com>
 * @license MIT
 * @version 1.0
 * @return void
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