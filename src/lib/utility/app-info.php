<?php

/**
 * app_info
 * 
 * Retrieving site setting info
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

    $configurations = new ConfigurationDao();
    $app_info = array();
    $results = $configurations->findConfigs();

    if (is_array($results)) {

        foreach ($results as $data) {

            if ($data['setting_name'] === 'app_key') {

                $app_info['app_key'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'app_url') {

                $app_info['app_url'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'site_name') {

                $app_info['site_name'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'site_tagline') {

                $app_info['site_tagline'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'site_description') {

                $app_info['site_description'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'site_keywords') {

                $app_info['site_keywords'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'site_email') {

                $app_info['site_email'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'post_per_page') {

                $app_info['post_per_page'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'post_per_rss') {

                $app_info['post_per_rss'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'post_per_archive') {

                $app_info['post_per_archive'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'comment_per_post') {

                $app_info['comment_per_post'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'permalink_setting') {

                $app_info['permalink_setting'] = $data['setting_value'];
            } elseif ($data['setting_name'] === 'timezone_setting') {

                $app_info['timezone_setting'] = $data['setting_value'];
            }
        }

        return $app_info;
    } else {

        return AppConfig::readConfiguration(invoke_config());
    }
}
