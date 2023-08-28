<?php
/**
 * app_url()
 * 
 * Retrieving URL configuration from database
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 * 
 */
function app_url()
{
  $appURL = medoo_get_where("tbl_settings", ['ID', 'setting_name', 'setting_value'], ['setting_name' => 'app_url']);
  return (is_array($appURL) && isset($appURL['setting_value'])) ? $appURL['setting_value'] : "";
}
