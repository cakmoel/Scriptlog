<?php
/**
 * Function build query
 * 
 * @category Function
 * @param string $base
 * @param array $query_data
 * @return string
 * 
 */
function build_query($base, $query_data)
{
  
  $url = basename($base) . "?". http_build_query($query_data);
  
  $safe_url = htmlspecialchars($url, ENT_COMPAT, 'UTF-8');
  
  return $safe_url;
  
}