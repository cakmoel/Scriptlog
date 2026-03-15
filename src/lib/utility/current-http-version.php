<?php
/**
 * current_http_version
 * 
 * checking http version sent by server and return it
 *
 * @category function
 * @license MIT
 * @version 1.0
 * @return string http version.
 * 
 */
function current_http_version() 
{

$http_version = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : '';

if (! in_array($http_version, ['HTTP/1.1', 'HTTP/2', 'HTTP/2.0'], true)) {

    $http_version = 'HTTP/1.0';

}

return $http_version;

}