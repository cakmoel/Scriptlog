<?php
/**
 * Find Request Function
 * find request URI path
 * 
 * @category Function
 * @return array
 * 
 */
function find_request($args)
{

  $dispatcher = new Dispatcher();
  
  return $dispatcher -> findRequestPath($args);

}