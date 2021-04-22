<?php
/**
 * Theme dir function
 * checking which theme actived and 
 * return it with application URL
 * 
 * @category Function
 * @return string
 * 
 */
function theme_dir()
{
    
  $active_theme = theme_identifier();

  $folder = $active_theme['theme_directory'].DS;

  return app_info()['app_url'].DS.APP_THEME.$folder;

}

/**
 * Theme Caller
 * 
 * @category functions
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * 
 */

function theme_identifier()
{
  
  $theme_init = new ThemeDao();
  
  return $theme_init->loadTheme('Y');
  
}

/**
 * call_theme_header
 * 
 * @category Function
 * @return void
 * 
 */
function call_theme_header()
{

if (file_exists(APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.'header.php')) {

  include(APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.'header.php');
     
} else {

  scriptlog_error("File header not found");

}

}

/**
 * call_theme_content
 *
 * @category Function
 * @param string $content
 * @return void
 * 
 */
function call_theme_content($content)
{

  if(file_exists(APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.basename($content.'.php'))) {

    include(APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.basename($content.'.php'));
   
  } else {

    scriptlog_error("file content not found");

  }

}

/**
 * call_theme_footer
 *
 * @category Function
 * @return void
 * 
 */
function call_theme_footer()
{

  if (file_exists(APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.'footer.php')) {

    include(APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.'footer.php');

  } else {

    scriptlog_error("File footer not found");  

  }

}

