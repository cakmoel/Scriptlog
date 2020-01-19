<?php
/**
 * Function purify dirty html
 * 
 * @category Function
 * @param string $dirty_html
 * 
 */

function purify_dirty_html($dirty_html)
{

  $purifier = new HTMLPurifier();
  
  $sanitized = $purifier->purify($dirty_html);

  return $sanitized;

}

