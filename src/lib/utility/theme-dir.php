<?php
/**
 * Theme dir function
 * checking which theme actived and select it
 * 
 * @category Function
 * @package  SCRIPTLOG/LIB/UTILITY
 * @return mixed
 * 
 */
function theme_dir()
{
    
  $themeActived = is_theme('Y');

  $folder = $themeActived['theme_directory'].DS;

  return app_info()['app_url'].APP_PUBLIC.DS.$folder;

}