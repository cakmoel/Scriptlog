<?php
/**
 * Theme_dir()
 * 
 * checking which is theme actived and return it directory with app's URL
 * 
 * @category function theme_dir checking active theme and return it directory with app's URL
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 * 
 */
function theme_dir()
{
    
  $active_theme = theme_identifier();

  return app_info()['app_url'].DS.APP_THEME.$active_theme['theme_directory'].DS;

}

/**
 * theme_identifier()
 * 
 * initialize theme actived
 * 
 * @category functions
 * @author M.Noermoehammad
 * 
 */
function theme_identifier()
{
  
  $theme_init = new ThemeDao();
  
  return (empty($theme_init->loadTheme('Y')) ?: $theme_init->loadTheme('Y'));

}

/**
 * call_theme_header
 * 
 * @category functions
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
function call_theme_header()
{

if (file_exists(APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.'header.php')) {

  include_once (APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.'header.php');
     
} else {

  scriptlog_error("File header not found");

}

}

/**
 * call_theme_content
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $content
 * 
 */
function call_theme_content($content = null)
{

  if (file_exists(APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.basename($content.'.php'))) {

    include_once (APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.basename($content.'.php'));
   
  } else {

    scriptlog_error("File content not found");

  }

}

/**
 * call_theme_footer
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
function call_theme_footer()
{

  if (file_exists(APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.'footer.php')) {

    include_once (APP_ROOT.APP_THEME.theme_identifier()['theme_directory'].DS.'footer.php');

  } else {

    scriptlog_error("File footer not found");  

  }

}