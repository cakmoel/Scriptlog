<?php
/**
 * Check configuration file function
 * checking whether configuration file exists
 *
 * @category Function
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