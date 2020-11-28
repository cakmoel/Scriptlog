<?php
/**
 * setplugin
 * Checking whether plugin actived then set it as plugin navigation on sidebar nav
 * 
 * @category Function
 * @author M.Noermoehammad
 * @license 
 * @version 1.0
 * @param string $access_level
 * 
 */
function set_plugin_navigation($plugin_name)
{
  
  $plugin = new PluginDao();
  
  $plugin_actived = $plugin->setMenuPlugin($plugin_name, plugin_authorizer());
  
  return $plugin_actived;
  
}

/**
 * plugin_authorizer
 *
 * checking user_level and return it
 * 
 * @return string
 * 
 */
function plugin_authorizer()
{

Authorization::setAuthInstance(new Authentication(new UserDao, new UserTokenDao, new FormValidator()));

return Authorization::authorizeLevel();

}

/**
 * invoke_plugin
 *
 * @param string $plugin_name
 * @param string $args
 * @return string|bool -- return false if plugin does not exists
 * 
 */
function invoke_plugin($plugin_name, $args)
{

 if ((is_plugin_exist($plugin_name) == true) && (is_plugin_enabled($plugin_name) == true)) {

   clip('clip_'.$plugin_name, null, function($plugin_name) { return $plugin_name; });

   $invoke_plugin = clip('clip_'.$plugin_name, $args);

   return $invoke_plugin;

 } else {

    return false;

 }
 
}

/**
 * is_plugin_exists
 *
 * @param string $plugin_name
 * @return boolean
 * 
 */
function is_plugin_exist($plugin_name)
{

  $plugin =  new PluginDao();

  $is_plugin_exists = $plugin->pluginExists($plugin_name);

  $is_exists = ($is_plugin_exists) ? true : false;

  return $is_exists;

}

/**
 * is_plugin_enabled
 *
 * checking whether plugin enabled
 * 
 * @param string $plugin_name
 * @return boolean
 * 
 */
function is_plugin_enabled($plugin_name)
{
  $plugin = new PluginDao($plugin_name);

  $is_plugin_actived = $plugin->isPluginActived($plugin_name);

  $is_enabled = ($is_plugin_actived) ? true : false;

  return $is_enabled;

}