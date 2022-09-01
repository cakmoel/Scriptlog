<?php
/**
 * purify_dirty_html()
 * 
 * clean and sanitize bad html code with HTMLPurifier
 * 
 * @category function
 * @license MIT
 * @version 1.0
 * @see https://paragonie.com/blog/2015/06/preventing-xss-vulnerabilities-in-php-everything-you-need-know
 * @param string $dirty_html
 * 
 */

function purify_dirty_html($dirty_html)
{

  $config = HTMLPurifier_Config::createDefault();
  
  $purifier = new HTMLPurifier($config);
  
  return $purifier->purify($dirty_html);

}

