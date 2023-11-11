<?php
/**
 * system_software
 * 
 * @category function
 * @author Nirmala Khanza 
 * @license MIT
 * @version 1.0
 * @return mixed
 * 
 */
function system_software()
{
  $config = class_exists('AppConfig') ? AppConfig::readConfiguration(invoke_config()) : "";
  $system_software = isset($config['os']['system_software']) ? $config['os']['system_software'] : "";
  return (!empty($system_software)) ? $system_software : "";
}