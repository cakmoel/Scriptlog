<?php
/**
 * User info
 * retrieve user records from database based on their login
 *
 * @param object $authenticator
 * @param string $user_login
 * @return mixed
 * 
 */
function user_info($authenticator, $user_login)
{

  $info = array();

  if (is_object($authenticator)) {

      $info = $authenticator -> findUserByLogin($user_login);

  }

  return $info;

}