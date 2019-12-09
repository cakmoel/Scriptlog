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

  $config = HTMLPurifier_Config::createDefault();
  $config->set('HTML.Doctype', 'XHTML 1.0 Strict');
  $config->set('Attr.AllowedClasses', 'special');
  $config->set('Core.Encoding', 'UTF-8');

  $allowedElements = [
    'p[style]',
    'br',
    'b',
    'strong',
    'i',
    'em',
    's',
    'u',
    'ul',
    'ol',
    'li',
    'span[class|data-custom-id|contenteditable]',
    'table[border|cellpadding|cellspacing]',
    'tbody',
    'tr',
    'td[valign]',
 ];

  $config->set('HTML.Allowed', implode(',', $allowedElements));

  $def = $config->getHTMLDefinition(true);
  $def->addAttribute('span', 'data-custom-id', 'Text');
  $def->addAttribute('span', 'contenteditable', 'Text');

  $purifier = new HTMLPurifier($config);
  $purifier->purify($dirty_html);

  return $purifier;

}