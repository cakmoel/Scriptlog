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

  $data_sanitized = $sanitizer->sanitasi(sanitize_string($str), $type);

  return $data_sanitized;

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
  
  $filter = $mysqli->filterData($str);
  
  return $filter;
  
}