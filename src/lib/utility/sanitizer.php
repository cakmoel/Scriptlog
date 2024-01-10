<?php
/**
 * Sanitizer
 *
 * @category Function
 * @author M.Noermoehammad
 * @param string $str
 * @param string $type
 * 
 */
function sanitizer($str, $type)
{
  $sanitizer = class_exists('Sanitize') ? new Sanitize() : "";
  return $sanitizer->sanitasi(sanitize_string($str), $type);
}

/**
 * sanitize_string
 *
 * @category Function
 * @param string $str
 * 
 */
function sanitize_string($str)
{
  $str = class_exists('Sanitize') ? Sanitize::mildSanitizer($str) : "";  

  $mysqli = class_exists('DbMySQLi') ? new DbMySQLi() : "";
  
  return $mysqli->filterData($str);
  
}