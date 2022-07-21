<?php
/**
 * Check configuration file function
 * checking whether configuration file exists
 *
 * @category Function
 * @version 1.0
 * @license MIT
 * @return void
 * 
 */
function check_config_file($filename)
{

 if (file_exists($filename)) {

    return true;

 } else {

    return false;

 }

}

/**
 * invoke_config()
 * 
 * invoking configuration file
 *
 * @category function
 * @return void
 * 
 */
function invoke_config()
{

  $configuration_file = __DIR__ . '/../../config.php';

  $sample_file = __DIR__ . '/../../config.sample.php';
 
  if (!check_config_file($configuration_file)) {

      return $sample_file;

  } else {

      return $configuration_file;

  }
  
}