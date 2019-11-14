<?php
/**
 * Prevent Injection Function
 * 
 * @param string $data
 * @return string
 */
function prevent_injection($data)
{
    
  $data = @trim(stripslashes(strip_tags(htmlspecialchars($data, ENT_QUOTES))));
  return $data;
    
}