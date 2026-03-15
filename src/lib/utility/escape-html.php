<?php
/**
 * escape_html()
 * 
 * Escape HTML, HTML Atrributes, JavaScript, CSS and Url.
 * This function for escaping data for output not be misused for filtering input data
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $input The input string
 * @param string $type
 * @param string $encoding Character encoding
 * @param string $mode | mode = base or mode = various
 * @uses laminas-escaper
 * @see https://paragonie.com/blog/2015/06/preventing-xss-vulnerabilities-in-php-everything-you-need-know
 * @see https://docs.laminas.dev/laminas-escaper/
 * @see https://docs.laminas.dev/laminas-escaper/escaping-html/
 * @see https://docs.laminas.dev/laminas-escaper/escaping-html-attributes/
 * @see https://docs.laminas.dev/laminas-escaper/escaping-javascript/
 * @see https://docs.laminas.dev/laminas-escaper/escaping-css/
 * @see https://docs.laminas.dev/laminas-escaper/escaping-url/
 * @return string
 * 
 */
function escape_html($input, $type = 'html', $encoding = 'utf-8', $mode = 'base')
{

  $escaper = new Laminas\Escaper\Escaper($encoding);

  switch ($mode) {

    default:
    case 'base':

      return base_escape($input, $type, $escaper); 

    break;

    case 'various':

      return various_escape($input, $type, $escaper);

    break;

  }

}

/**
 * base_escape
 *
 * @param string $input
 * @param [type] $type
 * @param object new Laminas\Escaper|Escaper $escaper
 * @return void
 */
function base_escape($input, $type, $escaper)
{

 if ($type === 'html_attributes') {

  return is_object($escaper) ? $escaper->escapeHtmlAttr($input) : "";

 } elseif ($type === 'html') {

  return is_object($escaper) ? $escaper->escapeHtml($input) : "";

 }

}

/**
 * various_escape
 *
 * @param string $input
 * @param string $type
 * @param object $escaper
 */
function various_escape($input, $type, $escaper)
{

  if ($type === 'js') {
    
    return is_object($escaper) ? $escaper->escapeJs($input) : "";

  } elseif ($type === 'css') {

    return is_object($escaper) ? $escaper->escapeCss($input) : "";
  
  } elseif ($type === 'url') {

    return is_object($escaper) ? $escaper->escapeUrl($input) : "";

  } 

}