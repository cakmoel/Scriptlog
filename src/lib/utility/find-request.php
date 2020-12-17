<?php
/**
 * find_request_path
 * find request URI path
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @uses Dispatcher::findRequestPath($args)
 * @param int|num $args
 * @return array
 * 
 */
function find_request_path($args)
{

  return Dispatcher::findRequestPath($args);
  
}

/**
 * find_request_param
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @return void
 * 
 */
function find_request_param()
{
 return Dispatcher::findRequestParam();
}