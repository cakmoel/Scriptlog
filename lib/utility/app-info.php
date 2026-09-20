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

    $settings = function_exists('app_settings') ? app_settings() : array();

    $app_info = [
        'site_name' => isset($settings['site_name']) ? $settings['site_name'] : '',
        'site_tagline' => isset($settings['site_tagline']) ? $settings['site_tagline'] : '',
        'site_description' => isset($settings['site_description']) ? $settings['site_description'] : '',
        'site_keywords' => isset($settings['site_keywords']) ? $settings['site_keywords'] : '',
        'site_email' => isset($settings['site_email']) ? $settings['site_email'] : '',
        'app_key' => isset($settings['app_key']) ? $settings['app_key'] : '',
        'app_url' => isset($settings['app_url']) ? $settings['app_url'] : '',
        'post_per_page' => isset($settings['post_per_page']) ? $settings['post_per_page'] : '',
        'post_per_rss' => isset($settings['post_per_rss']) ? $settings['post_per_rss'] : '',
        'post_per_archive' => isset($settings['post_per_archive']) ? $settings['post_per_archive'] : '',
        'comment_per_post' => isset($settings['comment_per_post']) ? $settings['comment_per_post'] : '',
        'permalink_setting' => isset($settings['permalink_setting']) ? $settings['permalink_setting'] : '',
        'timezone_setting' => isset($settings['timezone_setting']) ? $settings['timezone_setting'] : '',
    ];

    if ($app_info['site_name'] === '' && $app_info['app_url'] === '') {
        // No settings were read (e.g. no database connection) - fall back to the
        // configuration file, preserving the historical behavior of app_info().
        $config = (class_exists('AppConfig')) ? AppConfig::readConfiguration(invoke_config()) : [];

        if (is_array($config)) {
            $app_info['site_name'] = (isset($config['app']['name'])) ? $config['app']['name'] : $app_info['site_name'];
            $app_info['site_email'] = (isset($config['app']['email'])) ? $config['app']['email'] : $app_info['site_email'];
            $app_info['app_url'] = (isset($config['app']['url'])) ? $config['app']['url'] : $app_info['app_url'];
            $app_info['app_key'] = (isset($config['app']['key'])) ? $config['app']['key'] : $app_info['app_key'];
        }
    }

    return $app_info;
}
