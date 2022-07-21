<?php
/**
 * add_http
 * 
 * add http prefix to URL when it missing
 *  
 * @category function
 * @see https://stackoverflow.com/questions/2762061/how-to-add-http-if-it-doesnt-exists-in-the-url
 * @param string $url
 * @return string
 * 
 */

function add_http($url)
{

 if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        
    $url = 'http://'. $url;
    
  }
    
  return $url;
  
}

/**
 * add_scheme
 *
 * @category function
 * @license MIT
 * @version 1.0
 * @param string $url
 * @param string $scheme
 * @return string
 * 
 */
function add_scheme($url, $scheme = 'http://')
{
    return parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url;
}