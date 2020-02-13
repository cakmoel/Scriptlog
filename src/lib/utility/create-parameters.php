<?php
/**
 * create parameters
 * to prevent bad guys from tampering with variable passed in the URL
 *
 * @category Function
 * @see PHP 5 Power Programming
 * @param array $params
 * @return void
 * 
 */
function create_parameters()
{
  
  $_SESSION['args'] = md5(uniqid(rand(), true));

  $parameters = $_SESSION['args'];

  return $parameters;

}