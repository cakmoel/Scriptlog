<?php

/**
 * app_info()
 *
 * Retrieving site setting information
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return array
 *
 */
function app_info()
{

    $app_info = [];
    
    $conn = function_exists('medoo_init') ? medoo_init() : "";

    if (is_object($conn) && method_exists($conn, 'select')) {
        $results = $conn->select("tbl_settings", ["setting_name", "setting_value"]);
    } elseif (is_object($conn) && method_exists($conn, 'dbSelect')) {
        $results = $conn->dbSelect("SELECT setting_name, setting_value FROM tbl_settings", [], PDO::FETCH_ASSOC);
    } else {
        return (class_exists('AppConfig')) ? AppConfig::readConfiguration(invoke_config()) : "";
    }

    if (is_array($results)) {
        foreach ($results as $data) {
            // Handle both array and object formats
            $settingName = is_array($data) ? ($data['setting_name'] ?? '') : ($data->setting_name ?? '');
            $settingValue = is_array($data) ? ($data['setting_value'] ?? '') : ($data->setting_value ?? '');

            if ($settingName == 'app_key') {
                $app_info['app_key'] = $settingValue;
            } elseif ($settingName == 'app_url') {
                $app_info['app_url'] = $settingValue;
            } elseif ($settingName === 'site_name') {
                $app_info['site_name'] = $settingValue;
            } elseif ($settingName === 'site_tagline') {
                $app_info['site_tagline'] = $settingValue;
            } elseif ($settingName === 'site_description') {
                $app_info['site_description'] = $settingValue;
            } elseif ($settingName === 'site_keywords') {
                $app_info['site_keywords'] = $settingValue;
            } elseif ($settingName === 'site_email') {
                $app_info['site_email'] = $settingValue;
            } elseif ($settingName === 'post_per_page') {
                $app_info['post_per_page'] = $settingValue;
            } elseif ($settingName === 'post_per_rss') {
                $app_info['post_per_rss'] = $settingValue;
            } elseif ($settingName === 'post_per_archive') {
                $app_info['post_per_archive'] = $settingValue;
            } elseif ($settingName === 'comment_per_post') {
                $app_info['comment_per_post'] = $settingValue;
            } elseif ($settingName === 'permalink_setting') {
                $app_info['permalink_setting'] = $settingValue;
            } elseif ($settingName === 'timezone_setting') {
                $app_info['timezone_setting'] = $settingValue;
            }
        }

        return (is_array($app_info)) ? $app_info : "";
    } else {
        return (class_exists('AppConfig')) ? AppConfig::readConfiguration(invoke_config()) : "";
    }
}
