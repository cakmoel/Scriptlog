<?php
/**
 * Sanitizer
 *
 * @category Function
 * @author M.Noermoehammad
 * @param string $str
 * @param string $type
 * @return void
 * 
 */
function sanitizer($str, $type)
{
  $sanitizer = new Sanitize();
  return $sanitizer->sanitasi(sanitize_string($str), $type);
}

/**
 * sanitize_string
 *
 * @category Function
 * @param string $str
 * @return void
 * 
 */
function sanitize_string($str)
{
  $str = Sanitize::mildSanitizer($str);  

  $mysqli = new DbMySQLi();
  
  return $mysqli->filterData($str);
  
}