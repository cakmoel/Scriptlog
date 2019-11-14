<?php
/**
 * Theme Meta Function 
 * Display meta tag, link tag, title tag based on client request
 *  
 */
function theme_meta()
{
  $match = find_request(0);
  $param1 = find_request(1);
  $param2 = find_request(2);

  $meta_title = (!empty(app_info()['site_name'])) ? app_info()['site_name'] : $_SERVER['REQUEST_URI'];
  $theme_dir = theme_dir();

  switch ($match) {

      case 'post':
 
        meta_tag();
        title_tag($param2);
          
        break;

      case 'page':

        meta_tag();
        title_tag($param1);

        break;

      case 'blog':

        meta_tag();
        title_tag("Blog");
          
        break;
      
      case 'category':
          
        meta_tag();
        title_tag($param1);
          
        break;

       default:
          
          meta_tag();
          title_tag($meta_title);
          
          break;

  }
  
}

// function title tag
function title_tag($title)
{
    print<<<_HTML_

 <title>$title</title>

_HTML_;

}

// function tag meta
function meta_tag()
{
  print<<<_HTML_

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="all,follow">

_HTML_;

}
