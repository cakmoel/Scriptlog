<?php
/**
 * Collection of theme functions
 * handling of common theme content functionality
 *
 * @category functions
 * @license MIT
 * @version 1.0
 * 
 */

function grab_site_url()
{
  return app_url();
}

function grab_theme()
{
  return theme_dir();
}

function grab_cdn($link)
{

 $cdn_url = filter_var($link, FILTER_SANITIZE_URL);

 if (filter_var($cdn_url, FILTER_VALIDATE_URL)) {
  
    return htmlspecialchars(strip_tags(autolink($cdn_url)));
      
 }

}

function grab_canonical_link()
{
  return APP_PROTOCOL . '://' . APP_HOSTNAME .$_SERVER['REQUEST_URI'];

}

function grab_navigation()
{

  return front_navigation();

}

function grab_error_not_found()
{
  header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
  include __DIR__ . '/404.php';
}


