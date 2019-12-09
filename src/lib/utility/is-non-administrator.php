<?php
/**
 * is_non_administrator function
 * Class Wall will use this class to check user's access level
 * 
 * @category function
 * @return string
 * 
 */
function is_non_administrator()
{
  $user_level = false;

  $userDao = new UserDao();
  $userToken = new UserTokenDao();
  $validator = new FormValidator();
  $authenticator = new Authentication($userDao, $userToken, $validator);

  $accessLevel = $authenticator -> accessLevel();

  if(($accessLevel != 'administrator')) {
     
     $user_level = true;

  } else {

     $user_level = false;

  }

  return $user_level;

}