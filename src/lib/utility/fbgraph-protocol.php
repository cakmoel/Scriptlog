<?php
/**
 * fbgraph_protocol
 * 
 * @category function
 * @author   M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
function fbgraph_protocol($locale, $site_name, $id, $post_image, $title, $desc, $type, $post_url, $width, $height)
{
  
  $ogp = new OpenGraphProtocol();
  $imageGraph = new OpenGraphProtocolImage();
  
  $imageGraph -> setURL(app_info()['app_url'].APP_PUBLIC.DS.'files'.DS.'pictures'.DS.$post_image);
  $imageGraph -> setSecureURL(app_info()['app_url'].APP_PUBLIC.DS.'files'.DS.'pictures'.DS.$post_image);
  $imageGraph -> setType('image/jpeg');
  $imageGraph -> setWidth($width);
  $imageGraph -> setHeight($height);

  $ogp -> setLocale($locale);
  $ogp -> setSiteName($site_name);
  $ogp -> setTitle($title);
  $ogp -> setDescription($desc);
  $ogp -> setType($type);
  $ogp -> setURL($post_url);
  $ogp -> setDeterminer("");
  $ogp -> addImage($imageGraph);

  return $ogp -> toHTML();

}

