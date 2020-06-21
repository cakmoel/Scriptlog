<?php
/**
 * get_ip_address function
 * Get IP Address
 * 
 * @category  Function
 * @link https://stackoverflow.com/questions/1634782/what-is-the-most-accurate-way-to-retrieve-a-users-correct-ip-address-in-php
 * @return string
 * 
 */
function get_ip_address()
{
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {

        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                    
    }

    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
        
        if (array_key_exists($key, $_SERVER) === true){
            
            foreach (explode(',', $_SERVER[$key]) as $ip){
                
                $ip = trim($ip); // just to be safe
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                    
                    return $ip;

                }

            }

        }

    }
    
}

/**
 * zend_ip_address function
 * get ip address from proxy if it's provided
 * 
 * @uses RemoteAddress::getIpAddress
 * @see https://github.com/zendframework/zend-http/blob/master/src/PhpEnvironment/RemoteAddress.php
 * @see https://framework.zend.com/apidoc/2.1/classes/Zend.Http.PhpEnvironment.RemoteAddress.html
 * @return void
 */
function zend_ip_address()
{
 
  $ip = new RemoteAddress();

  return $ip->getIpAddress();

}