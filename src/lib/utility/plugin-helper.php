<?php
/**
 * read_plugin_ini()
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $plugin_directory
 * @return void
 * 
 */
function read_plugin_ini($plugin_directory)
{

$plugin_ini = null;

if (!in_array('plugin.ini', plugin_scanner($plugin_directory))) {

     scriptlog_error("Plugin info does not exists");

} else {

     $plugin_ini = parse_ini_file($plugin_directory.DS.'plugin.ini');

}

return $plugin_ini;

}

/**
 * enable_plugin
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $plugin_directory
 * @return bool
 * 
 */
function enable_plugin($plugin_directory)
{

$enabled = false;

$plugin_ini = read_plugin_ini($plugin_directory);

$plugin_loader = $plugin_ini['plugin_loader'];

if (!file_exists($plugin_directory.DS.basename($plugin_loader))) {

     $enabled = false;

} else {

  if (rename($plugin_directory.DS.basename($plugin_loader), __DIR__ . '/../../'.APP_ADMIN.DS)) {

     $enabled = true;
           
  } else {

     $enabled = false;

  }

}

return $enabled;

}

function disable_plugin($plugin_directory) 
{

$disabled = true;

$plugin_ini = read_plugin_ini($plugin_directory);

$plugin_loader = $plugin_ini['plugin_loader'];

if (file_exists(__DIR__ . '/../../'.APP_ADMIN.DS.$plugin_loader)) {

   
}

}

/**
 * plugin_scanner()
 *
 * Scanning plugin directory selected
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $plugin_directory
 * @return void
 * 
 */
function plugin_scanner($plugin_directory)
{

ScriptlogScanner::setDirectory($plugin_directory);

return ScriptlogScanner::scan();

}