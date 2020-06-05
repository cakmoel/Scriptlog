<?php
/**
 * read and write configuration file
 *
 * @category function
 * @see https://stackoverflow.com/questions/2237291/reading-and-writing-configuration-files
 * @param string $filename
 * @return mixed
 * 
 */

function read_config($filename)
{
  
 $read_configuration = AppConfig::readConfiguration($filename);

 return $read_configuration;

}

function write_config($filename, array $configuration)
{

  $write_configuration = AppConfig::writeConfiguration($filename, $configuration);
  
  return $write_configuration;

}