<?php
/**
 * escape_html()
 * 
 * Escape HTML, HTML Atrributes, JavaScript, CSS and Url.
 * This function for escaping data for output not be misused for filtering input data
 * 
 * @param string $input The input string
 * @param string $encoding Character encoding
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
function escape_html($input, $type = 'html', $encoding = 'utf-8')
{

  $escaper = new Laminas\Escaper\Escaper($encoding);

  switch ($type) {

    case 'html_attributes':
      
      $output = $escaper->escapeHtmlAttr($input);

      return $output;

      break;

    case 'js':

      $output = $escaper->escapeJs($input);

      return $output;

      break;

    case 'css':

      $output = $escaper->escapeCss($input);

      return $output;

      break;

    case 'url':

      $output = $escaper->escapeUrl($input);

      return $output;

      break;
    
    default:

      return $escaper->escapeHtml($input); 

      break;

  }
  
}