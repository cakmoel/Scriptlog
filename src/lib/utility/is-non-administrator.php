<?php
/**
 * is_non_administrator
 * 
 * this function will be checking user's access level
 * 
 * @category function
 * @author M.Noermoehammad
 * @return string
 * 
 */
function is_non_administrator()
{
  $user_level = false;

  $userDao = class_exists('UserDao') ? new UserDao() : "";
  $userToken = class_exists('UserTokenDao') ? new UserTokenDao() : "";
  $validator = class_exists('FormValidator') ? new FormValidator() : "";
  $authenticator = class_exists('Authentication') ? new Authentication($userDao, $userToken, $validator) : "";

  $accessLevel = $authenticator->accessLevel();

  if ($accessLevel !== 'administrator') {
     
     $user_level = true;

  } else {

     $user_level = false;

  }

  return $user_level;

}