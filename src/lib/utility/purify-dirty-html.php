<?php
/**
 * Function purify dirty html
 * 
 * @param string $dirty_html
 * 
 */

function purify_dirty_html($dirty_html)
{

  $config = HTMLPurifier_Config::createDefault();
  $config->set('HTML.Doctype', 'XHTML 1.0 Strict');
  $config->set('Attr.AllowedClasses', 'special');

  $purifier = new HTMLPurifier($config);
  $purifier->purify($dirty_html);

  return $purifier;

}