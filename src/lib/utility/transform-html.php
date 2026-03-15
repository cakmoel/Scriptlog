<?php
/**
 * transform_html
 * 
 * Converts a string from UTF-8 to ISO-8859-1, replacing invalid or unrepresentable characters 
 * 
 * @param string $string
 * @param integer $length
 * @return string
 * 
 */
function transform_html($string, $length)
{
  $string = trim($string);
  
  $string = function_exists('mb_convert_encoding') ? mb_convert_encoding($string, ' ISO-8859-1', 'UTF-8') : "";
  
  $string = htmlentities($string, ENT_QUOTES, 'UTF-8');
  
  $string = str_replace("#", "&#35;", $string);
  
  $string = str_replace("%", "&#37;", $string);
  
  $length = intval($length);

  if ($length > 0) {

      $string = substr($string, 0, $length);

  }

  return $string;
  
}