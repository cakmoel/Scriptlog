<?php
/**
 * build_query
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $base
 * @param array $query_data
 * @return string
 * 
 */
function build_query($base, $query_data)
{
  $url = basename($base) . "?". http_build_query($query_data);
  return escape_html($url);
}