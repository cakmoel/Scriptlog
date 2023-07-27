<?php
/**
 * app_key()
 * 
 * checking if application key is recognized and equal 
 * between application key on database and configuration file
 * then return it otherwise will be false
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

  $configKey = isset($config['app']['key']) ? $config['app']['key'] : "";
  
  return (!empty($configKey)) ? $configKey : "";
}

/**
 * check_app_key()
 *
 * @category function
 * @param string $key
 * @return bool
 * 
 */
function check_app_key($key)
{
  $appKey = false;
  $grabKey = grab_data_key();

  if ($key === $grabKey) {
      
    $appKey = true;

  } elseif (strcmp($key, $grabKey) === 0) {

    $appKey = true;
  } else {

    $appKey = false;
  }

  return $appKey;
}

/**
 * grab_data_key()
 *
 * @return mixed
 * 
 */
function grab_data_key()
{
  return medoo_get_where("tbl_settings", "setting_value", [
    "setting_name" => "app_key"
  ]);
}