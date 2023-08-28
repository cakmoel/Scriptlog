<?php

/**
 * distrib_name()
 * 
 * reading operating system distribution name for linux distro 
 * within configuration file
 * 
 * @category function
 * @author Nirmala Khanza
 * @license MIT
 * @version 1.0
 * @return mixed
 * 
 */
function distrib_name()
{
  $config = class_exists('AppConfig') ? AppConfig::readConfiguration(invoke_config()) : "";

  $distrib_name = isset($config['os']['distrib_name']) ? $config['os']['distrib_name'] : "";

  return (!empty($distrib_name)) ? $distrib_name : "";
}