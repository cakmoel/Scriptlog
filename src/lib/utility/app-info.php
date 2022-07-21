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

    switch ($data['setting_name']) {
      
        case 'app_key':

          $app_info['app_key'] = $data['setting_value'];

          break;

        case 'app_url':

          $app_info['app_url'] = $data['setting_value'];

          break;

        case 'site_name':

          $app_info['site_name'] = $data['setting_value'];

           break;

        case 'site_tagline':

           $app_info['site_tagline'] = $data['setting_value'];

           break;

        case 'site_description':

          $app_info['site_description'] = $data['setting_value'];

          break;

        case 'site_keywords':

          $app_info['site_keywords'] = $data['setting_value'];

          break;

        case 'site_email':

          $app_info['site_email'] = $data['setting_value'];

          break;

        case 'permalink_setting':

          $app_info['permalink_setting'] = $data['setting_value'];

          break;

    }
    
  }
 
 } else {

  $app_info = AppConfig::readConfiguration(invoke_config());

 }
 
 return $app_info;

}