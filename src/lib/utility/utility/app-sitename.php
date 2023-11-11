<?php
/**
 * app_sitename()
 * 
 * @category function
 * @author nirmala khanza 
 * @license MIT
 * @version 1.0
 * 
 */
function app_sitename()
{
    $siteName = function_exists('medoo_get_where') ? medoo_get_where("tbl_settings", ['ID', 'setting_name', 'setting_value'], ['setting_name' => 'site_name']) : "";
    return (is_array($siteName) && isset($siteName['setting_value'])) ? $siteName['setting_value'] : "";
}