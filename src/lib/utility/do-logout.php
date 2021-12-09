<?php 
/**
 * do_logut function
 *
 * @param object $authenticator
 * @return void
 */
function do_logout($authenticator)
{

 if(is_object($authenticator)) {

    return $authenticator->logout();

 }

}

/**
 * do_logout_id
 *
 * @return string
 * 
 */
function do_logout_id()
{

$prefix = Session::getInstance()->scriptlog_fingerprint;

$id_logout = uniqid($prefix, true);

if(empty($_SESSION['loggingOut'])) {

   $_SESSION['loggingOut'] = array();

}

$_SESSION['loggingOut'][$id_logout] = true;

return $id_logout;

}

/**
 * verify_logout_id
 *
 * @param [type] $logOutId
 * @return boolean
 */
function verify_logout_id($id_logout)
{

if(isset($_SESSION['loggingOut'][$id_logout])) {

   unset($_SESSION['loggingOut'][$id_logout]);
   return true;
  
}

return false;

}