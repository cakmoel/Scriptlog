<?php
/**
 * Transform HTML Function
 * 
 * @param string $string
 * @param integer $length
 * 
 */
function transform_html($string, $length = null)
{
  $string = trim($string);
  
  $string = utf8_decode($string);
  
  $string = htmlentities($string, ENT_QUOTES, 'UTF-8');
  
  $string = str_replace("#", "&#35;", $string);
  
  $string = str_replace("%", "&#37;", $string);
  
  $length = intval($length);

  if ($length > 0) {

      $string = substr($string, 0, $length);

  }

  return $string;
  
}