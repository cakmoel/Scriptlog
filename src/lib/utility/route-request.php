<?php
/**
 * Route Request Function
 * this function will be called in index.php file
 * on top of our site directory
 * 
 * @return mixed
 * 
 */
function route_request()
{
  
  $dispatcher = new Dispatcher();
  
  return $dispatcher -> dispatch();

}