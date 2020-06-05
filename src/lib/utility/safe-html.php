<?php
/**
 * Safe HTML function
 * Sanitizing text results from database
 * 
 * @param string $text
 * @return void
 * 
 */
function safe_html($data)
{
    
  return htmlspecialchars(stripslashes(trim($data)), ENT_COMPAT|ENT_HTML5, 'UTF-8', false);

}
/**
 *  HTML parsing, filtering and sanitization
 *
 * @param string $data
 * 
 */
function safe_filter_html($data)
{
 
  $html_filter = new Html();

  return $html_filter->filter($data);
  
}