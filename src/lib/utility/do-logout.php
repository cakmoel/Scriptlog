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