<?php

/**
 * app_reading_setting()
 *
 * @category function
 * @author MNoermoehammad
 * @return array
 *
 */
function app_reading_setting()
{

    $settings = function_exists('app_settings') ? app_settings() : array();

    $reading_settings = array();

    foreach (['post_per_page', 'post_per_rss', 'post_per_archive', 'comment_per_post'] as $name) {
        if (isset($settings[$name])) {
            $reading_settings[$name] = $settings[$name];
        }
    }

    return $reading_settings;
}
