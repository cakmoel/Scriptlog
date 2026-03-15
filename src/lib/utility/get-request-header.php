<?php
/**
 * get_request_header
 *
 * To get an associative array of HTTP request headers formatted similarly to get_headers()
 * 
 * @see https://www.php.net/manual/en/reserved.variables.server.php#99395
 * @return mixed
 * 
 */
function get_request_header()
{

$headers = array();
    
foreach ($_SERVER as $key => $value) {
    
    if (strpos($key, 'HTTP_') === 0) {
        
      $headers[str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))))] = $value;
        
    }

}
    
return $headers;

}