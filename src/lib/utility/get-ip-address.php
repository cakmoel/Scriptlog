<?php
/**
 * get_ip_address()
 * 
 * Get IP Address
 * 
 * @category function
 * @see https://stackoverflow.com/questions/1634782/what-is-the-most-accurate-way-to-retrieve-a-users-correct-ip-address-in-php
 * @see https://blog.ircmaxell.com/2012/11/anatomy-of-attack-how-i-hacked.html
 * @return string
 * 
 */
function get_ip_address()
{

 if ( true === detect_proxy_by_headers() ) {

  return (getenv('REMOTE_ADDR', true) ? getenv('REMOTE_ADDR') : zend_ip_address() );

 } else {

  return Util::get_client_ip();

 }
    
}

/**
 * zend_ip_address()
 * get ip address from proxy if it's provided
 * 
 * @uses RemoteAddress::getIpAddress
 * @see https://github.com/zendframework/zend-http/blob/master/src/PhpEnvironment/RemoteAddress.php
 * @see https://framework.zend.com/apidoc/2.1/classes/Zend.Http.PhpEnvironment.RemoteAddress.html
 * @return void
 * 
 */
function zend_ip_address()
{
 
  $ip = new RemoteAddress();

  return $ip->getIpAddress();

}