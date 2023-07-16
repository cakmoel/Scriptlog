<?php
/**
 * access_control_list
 * Checking users's action privilege 
 * on admin directory wherein navigation menu
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $action
 * @return bool
 * 
 */
function access_control_list($action = null)
{
  
  $user_dao = class_exists('UserDao') ? new UserDao() : "";
  
  $user_token = class_exists('UserTokenDao') ? new UserTokenDao() : "";
  
  $form_validator = class_exists('FormValidator') ? new FormValidator() : "";
  
  $authenticator = class_exists('Authentication') ? new Authentication($user_dao, $user_token, $form_validator) : "";
  
  return $authenticator->userAccessControl($action);

}