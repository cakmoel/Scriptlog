<?php
/**
 * html()
 *
 * @param string $text
 * @return string
 * 
 */
function html($text)
{
  return safe_html($text);
}

/**
 * htmlout()
 *
 * @param string $text
 * 
 */
function htmlout($text)
{
  return escape_html(html($text));
}

/**
 * markdown_html()
 *
 * @param string $text
 * 
 */
function markdown_html($text)
{

  // strong emphasis
  $text = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text);
  $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

  // emphasis
  $text = preg_replace('/_([^_]+)_/', '<em>$1</em>', $text);
  $text = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $text);

  // convert windows (\r\n) to unix(\n)
  $text = str_replace("\r\n", "\n", $text);
  // convert macintosh(\r) to unix(\n)
  $text = str_replace("\r", "\n", $text);

  // paragraph
  $text = '<p>'. str_replace("\n\n", '<p></p>', $text) .'</p>';
  //Line breaks
  $text = str_replace("\n", '<br>', $text);

  // [linked text](link URL)
  $text = preg_replace('/\[([^\]]+)]\(([-a-z0-9._~:\/?#@!$&\'()*+,;=%]+)\)/i', '<a href="$2">$1</a>', $text);

  return $text;
  
}

/**
 * markdown_html_out
 *
 * @param string $text
 * 
 */
function markdown_html_out($text)
{
  echo markdown_html($text);
}

/**
 * copyright
 *
 * @return string
 */
function copyright()
{
  
  return '&copy;';

}

/**
 * year_on_footer
 *
 * @param string $start_year
 * 
 */
function year_on_footer($start_year)
{
  
  $this_year = date("Y");
  
  if ($start_year == $this_year) {
     
    echo $start_year;
     
  } else {
      
    echo " {$start_year} &#8211; {$this_year} ";

  }
             
}