<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * read_plugin_ini()
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $plugin_directory
 * @return mixed
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
 * Loads and initializes a plugin
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

if (empty($plugin_ini)) {
    return false;
}

$plugin_loader = $plugin_ini['plugin_loader'];
$pluginFile = $plugin_directory . DS . $plugin_loader . '.php';

if (!file_exists($pluginFile)) {
    
    $files = glob($plugin_directory . DS . '*.php');
    
    if (!empty($files)) {
        $pluginFile = $files[0];
    } else {
        return false;
    }
}

require_once $pluginFile;

$functionsFile = $plugin_directory . DS . 'functions.php';

if (file_exists($functionsFile)) {
    require_once $functionsFile;
}

return true;

}

/**
 * disable_plugin
 *
 * @param string $plugin_directory
 * @return bool
 * 
 */
function disable_plugin($plugin_directory) 
{

$disabled = true;

$plugin_ini = read_plugin_ini($plugin_directory);

$plugin_loader = $plugin_ini['plugin_loader'];

if (file_exists(__DIR__ . '/../../'.APP_ADMIN.DS.basename($plugin_loader))) {

  $disabled = true;
   
} else {

  $disabled = false;

}

return $disabled;

}

/**
 * plugin_scanner()
 *
 * Scanning plugin directory selected
 * 
 * @category function
 * @uses ScriptlogScanner::setDirectory
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $plugin_directory
 * @return array
 * 
 */
function plugin_scanner($plugin_directory)
{

ScriptlogScanner::setDirectory($plugin_directory);

return ScriptlogScanner::scan();

}