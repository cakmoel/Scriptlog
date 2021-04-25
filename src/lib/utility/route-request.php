<?php
/**
 * route_request()
 * 
 * this function will be called in index.php file
 * on top of our site directory
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return mixed
 * 
 */
function route_request($dispatcher)
{
  (is_object($dispatcher) ? $dispatcher->dispatch() : trigger_error("Scriptlog's internal server not working") ); 
}