<?php
/**
 * Load engine function
 * 
 * @category Function
 * @param array $directory
 * @return mixed
 * 
 */
function load_engine($directory = array())
{

  $loader = new Scriptloader();

  $loader -> setLibraryPaths($directory);

  return $loader -> runLoader(); 

}