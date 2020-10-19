<?php
/**
 * Escape all HTML, JavaScript, and CSS
 * 
 * @param string $input The input string
 * @param string $encoding Which character encoding are we using?
 * @see https://paragonie.com/blog/2015/06/preventing-xss-vulnerabilities-in-php-everything-you-need-know
 * @return string
 * 
 */
function escape_html($input, $encoding = "UTF-8")
{
  return htmlentities($input, ENT_QUOTES | ENT_HTML5, $encoding);
}

/**
 * escape_null_byte
 *
 * @param string $input
 * @return void
 * 
 */
function escape_null_byte($input)
{

 $input = str_replace(chr(0), '', $input);

 return $input;

}