<?php
use HtmlSanitizer\Sanitizer;
/**
 * Prevent Injection Function
 * 
 * @category Function
 * @see  http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed 
 * @see https://github.com/tgalopin/html-sanitizer/blob/master/docs/1-getting-started.md
 * @param string $data
 * @return string
 * 
 */
function prevent_injection($str)
{
    
  $filter = @trim(stripslashes(strip_tags(htmlspecialchars($str, ENT_COMPAT|ENT_HTML5, 'UTF-8'))));
  
  $sanitizer = \HtmlSanitizer\Sanitizer::create(['extensions' => ['basic']]);

  if (PHP_VERSION_ID >= 70100) {

     $safe_html = $sanitizer->sanitize($filter);

  } else {

     $safe_html = htmLawed($filter);

  }
  
  return $safe_html;

}
