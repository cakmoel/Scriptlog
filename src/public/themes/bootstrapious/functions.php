<?php
/**
 * functions.php
 * This file is collection of functions that will used by theme functionality
 * 
 */
function grab_site_url()
{
 return app_url();
}

function grab_site_key()
{
  return app_key();
}

function grab_theme()
{
 return theme_dir();
}

function grab_link_canonical()
{
  return APP_PROTOCOL . '://' . APP_HOSTNAME . $_SERVER['REQUEST_URI'];
}

function grab_cdn($link)
{
  $cdn_url = filter_var($link, FILTER_SANITIZE_URL);

  if (filter_var($cdn_url, FILTER_VALIDATE_URL)) {

    return htmlspecialchars(strip_tags(autolink($cdn_url)));
    
  }

}

function grab_post($slug = null)
{
  
}

function grab_navigation()
{
  return front_navigation();
}

function page_not_found()
{
  header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
  include __DIR__ . '/404.php';
}
