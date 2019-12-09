<?php
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
  
  if($theme_init->totalThemeRecords() > 0) {

    return $theme_init->loadTheme('Y');

  }

}

// call theme header
function call_theme_header()
{
  
  if(!theme_identifier()) {

     scriptlog_error("Theme is not found!", E_USER_ERROR);

  } else {

    if(file_exists(APP_ROOT.APP_PUBLIC.DS.theme_identifier()['theme_directory'].DS.'header.php')) {

      include(APP_ROOT.APP_PUBLIC.DS.theme_identifier()['theme_directory'].DS.'header.php');

    } else {

       scriptlog_error("file header.php not found", E_USER_NOTICE);

    }
     
  }

}

// call theme content
function call_theme_content($content)
{

  if(!theme_identifier()) {

    scriptlog_error("Theme is not found!", E_USER_ERROR);

  } else {

    if(file_exists(APP_ROOT.APP_PUBLIC.DS.theme_identifier()['theme_directory'].DS.basename($content.'.php'))) {

      include(APP_ROOT.APP_PUBLIC.DS.theme_identifier()['theme_directory'].DS.basename($content.'.php'));
     
    } else {

      scriptlog_error("file content not found", E_USER_NOTICE);

    }

  }

}

// call theme footer
function call_theme_footer()
{

  if(!theme_identifier()) {

    scriptlog_error("Theme is not found!", E_USER_ERROR);

  } else {

    if(file_exists(APP_ROOT.APP_PUBLIC.DS.theme_identifier()['theme_directory'].DS.'footer.php')) {

      include(APP_ROOT.APP_PUBLIC.DS.theme_identifier()['theme_directory'].DS.'footer.php');

    } else {

      scriptlog_error("file footer.php not found", E_USER_NOTICE);
    }
     
  }

}