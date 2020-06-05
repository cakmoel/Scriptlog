<?php
/**
 * Current request method
 * check and get current request method
 *
 * @return void
 * 
 */
function current_request_method()
{
    
 $current_request = (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : "";
 
 return $current_request;

}