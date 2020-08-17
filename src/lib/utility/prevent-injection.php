<?php
/**
 * Prevent Injection Function
 * 
 * @category Function
 * @uses htmLawed($text) htmLawed
 * @see  http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed 
 * @param string $data
 * @return string
 * 
 */
function prevent_injection($str)
{
    
  $str = @trim(stripslashes(strip_tags(htmlspecialchars($str, ENT_COMPAT|ENT_HTML5, 'UTF-8'))));
  
  $filter = htmLawed($str);

  return $filter;
    
}