<?php
/**
 * Access Control list Function
 * Checking users's action privilege 
 * on admin directory wherein navigation menu
 * 
 * @category Function
 * @param string $action
 * @return bool
 * 
 */
function access_control_list($action = null)
{
  
  $user_dao = new UserDao();
  
  $user_token = new UserTokenDao();
  
  $form_validator = new FormValidator();
  
  $authenticator = new Authentication($user_dao, $user_token, $form_validator);
  
  $access_control = $authenticator->userAccessControl($action);

  return $access_control;

}