<?php
/**
 * app_key
 * checking if application key is recognized and equal 
 * between application key on database and configuration file
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 * 
 */
function app_key()
{
  global $config;

  if ($config['app']['key'] === app_info()['app_key']) {

    return app_info()['app_key'];

  } elseif (strcmp($config['app']['key'], app_info()['app_key']) == 0) { 
  
    return app_info()['app_key'];

  } else {

    return false;

  }
  
}