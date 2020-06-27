<?php
/**
 * Function purify dirty html
 * 
 * @category Function
 * @see https://paragonie.com/blog/2015/06/preventing-xss-vulnerabilities-in-php-everything-you-need-know
 * @param string $dirty_html
 * 
 */

function purify_dirty_html($dirty_html)
{

  $config = HTMLPurifier_Config::createDefault();
  
  $purifier = new HTMLPurifier($config);
  
  $sanitized = $purifier->purify($dirty_html);

  return $sanitized;

}

