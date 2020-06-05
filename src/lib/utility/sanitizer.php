<?php
/**
 * Sanitizer function
 *
 * @param string $str
 * @param string $type
 * @return void
 * 
 */
function sanitizer($str, $type)
{
  $sanitizer = new Sanitize();

  $data_sanitized = $sanitizer -> sanitasi($str, $type);

  return $data_sanitized;

}