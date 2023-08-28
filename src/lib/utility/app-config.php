<?php
/**
 * read_config
 * read configuration file
 * 
 * @category function
 * @see https://stackoverflow.com/questions/2237291/reading-and-writing-configuration-files
 * @param string $filename
 * @return mixed
 * 
 */
function read_config($filename)
{
 return (class_exists('AppConfig')) ? AppConfig::readConfiguration($filename) : "";
}

/**
 * write_config
 * 
 * write configuration file
 * 
 * @category function
 * @param string $filename
 * @param array $configuration
 * @return void
 * 
 */
function write_config($filename, $configuration)
{
  return (class_exists('AppConfig')) ? AppConfig::writeConfiguration($filename, $configuration) : ""; 
}