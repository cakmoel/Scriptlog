<?php
/**
 * app_tagline
 *
 * retrieving tagline info from tbl_settings
 * 
 * @category functions
 * @author Nirmala Khanza 
 * @license MIT
 * @version 1.0
 *
 */
function app_tagline()
{
 $tagline = function_exists('medoo_get_where') ? medoo_get_where("tbl_settings", ['ID', 'setting_name', 'setting_value'], ['setting_name' => 'site_tagline']) : "";
 return (is_array($tagline) && isset($tagline['setting_value'])) ? $tagline['setting_value'] : "";
}