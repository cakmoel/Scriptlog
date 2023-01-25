<?php
/**
 * detect_proxy_by_headers
 * Detect proxies with a simple PHP test to determine if a user is hiding behind a proxy connection. 
 * Quickly evaluate an IP address with PHP to detect proxy connections.
 * 
 * @category function
 * @author Contributors
 * @license MIT
 * @version 1.0
 * @see https://www.ipqualityscore.com/articles/view/1/how-to-detect-proxies-with-php
 * @see https://stackoverflow.com/questions/33300877/how-do-you-detect-a-vpn-or-proxy-connection
 * @see https://stackoverflow.com/questions/858357/detect-clients-with-proxy-servers-via-php
 * @return bool
 * 
 */
function detect_proxy_by_headers()
{

$proxy_headers = [
    'HTTP_VIA',
	'VIA',
	'Proxy-Connection',
	'HTTP_X_FORWARDED_FOR',  
	'HTTP_FORWARDED_FOR',
	'HTTP_X_FORWARDED',
	'HTTP_FORWARDED',
	'HTTP_CLIENT_IP',
	'HTTP_FORWARDED_FOR_IP',
	'X-PROXY-ID',
	'MT-PROXY-ID',
	'X-TINYPROXY',
	'X_FORWARDED_FOR',
	'FORWARDED_FOR',
	'X_FORWARDED',
	'FORWARDED',
	'CLIENT-IP',
	'CLIENT_IP',
	'PROXY-AGENT',
	'HTTP_X_CLUSTER_CLIENT_IP',
	'FORWARDED_FOR_IP',
	'HTTP_PROXY_CONNECTION'
];

foreach ($proxy_headers as $header) {
    
    if( ( isset($_SERVER[$header]) ) && ( !empty($_SERVER[$header]) ) ) {

         return true;

    } else {

         return false;
         
    }

}

}