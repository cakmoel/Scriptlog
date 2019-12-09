<?php
/**
 * Route Request Function
 * this function will be called in index.php file
 * on top of our site directory
 * 
 * @category Function 
 * @return mixed
 * 
 */
function route_request()
{
  
  $dispatcher = new Dispatcher();
  
  return $dispatcher -> dispatch();

}