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

$configurations = new ConfigurationDao();
$reading_settings = array();
$results = $configurations->findReadingConfigs();

if (is_array($results)) {

    foreach ($results as $data) {

        switch ($data['setting_name']) {

            case 'post_per_page':
                
                 $reading_settings['post_per_page'] = $data['setting_value'];
                 
                break;
            
            case 'post_per_rss':
                
                $reading_settings['post_per_rss'] = $data['setting_value'];

                break;

            case 'post_per_archive':

                $reading_settings['post_per_archive'] = $data['setting_value'];

                break;

        }

    }

    return $reading_settings;

}

}