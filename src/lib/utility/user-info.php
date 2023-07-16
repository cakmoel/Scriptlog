<?php
/**
 * user_info()
 * 
 * retrieve user records from database based on their login
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param object $authenticator
 * @param string $user_login
 * @return mixed
 * 
 */
function user_info($authenticator, $user_login)
{

  $user_info = array();

  if (is_object($authenticator)) {

    $user_info = $authenticator->findUserByLogin($user_login);

  }

  return $user_info;

}