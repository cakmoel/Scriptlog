<?php
/**
 * prevent_injection()
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @uses htmLawed()
 * @see http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed 
 * @see https://paragonie.com/blog/2015/06/preventing-xss-vulnerabilities-in-php-everything-you-need-know
 * @see https://github.com/tgalopin/html-sanitizer/blob/master/docs/1-getting-started.md
 * @param string $data
 * @return string
 * 
 */
function prevent_injection($str)
{
  $filter = @trim(stripslashes(strip_tags(htmlspecialchars($str, ENT_COMPAT|ENT_HTML5, 'UTF-8'))));
  if (function_exists('htmLawed')) {
    return htmLawed($filter);
  }
}
