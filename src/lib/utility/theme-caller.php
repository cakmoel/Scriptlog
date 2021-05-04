<?php
/**
 * Theme_dir()
 * 
 * checking which is theme actived and return it with app's URL
 * 
 * @category function
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
 * theme_identifier()
 * 
 * initialize theme actived
 * 
 * @category functions
 * @return mixed
 * 
 */
function theme_identifier()
{
  
  $theme_init = new ThemeDao();
  
  return ( empty($theme_init->loadTheme('Y') ) ?: $theme_init->loadTheme('Y') );

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