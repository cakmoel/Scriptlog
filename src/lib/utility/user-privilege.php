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

   $userDao = class_exists('UserDao') ? new UserDao() : "";
   $userTokenDao = class_exists('UserTokenDao') ? new UserTokenDao() : "";
   $formValidator = class_exists('FormValidator') ? new FormValidator() : "";
   $authentication = class_exists('Authentication') ? new Authentication($userDao, $userTokenDao, $formValidator) : "";

   (class_exists('Authorization')) ? Authorization::setAuthInstance($authentication) : "";
          
   return Authorization::authorizeLevel();
  
 }
  
 if (isset(Session::getInstance()->scriptlog_session_level)) {
  
   return Session::getInstance()->scriptlog_session_level;
      
 }

 return false;
 
}