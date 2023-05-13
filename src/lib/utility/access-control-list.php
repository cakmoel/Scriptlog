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
  
  $user_dao = new UserDao();
  
  $user_token = new UserTokenDao();
  
  $form_validator = new FormValidator();
  
  $authenticator = new Authentication($user_dao, $user_token, $form_validator);
  
  return $authenticator->userAccessControl($action);

}