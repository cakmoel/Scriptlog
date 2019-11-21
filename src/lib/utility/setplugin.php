<?php
/**
 * setplugin function
 * Checking whether plugin actived then set it as plugin navigation on sidebar nav
 * 
 * @param string $access_level
 * 
 */
function setplugin($user_level, $plugin_level)
{
  $plugin = new PluginDao();
  $plugin_actived = $plugin -> setMenuPlugin($user_level, $plugin_level);
  return $plugin_actived;
}