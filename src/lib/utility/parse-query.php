<?php
/**
 * Parse Query Function 
 * 
 * @category Function
 * @source PHP.Net to parse out the query element from the output of parse_url()
 * @see   https://secure.php.net/manual/en/function.parse-url.php#95304
 * 
 */
function parse_query($var)
{
  $var  = parse_url($var, PHP_URL_QUERY);
  $var  = html_entity_decode($var);
  $var  = explode('&', $var);
  $arr  = array();

  foreach($var as $val) {

    $x          = explode('=', $val);
    $arr[$x[0]] = $x[1];

   }

  unset($val, $x, $var);
  return $arr;

}
