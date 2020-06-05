<?php
/**
 * 
 */

function html($text)
{
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function htmlout($text)
{
  echo html($text);
}

function markdown_html($text)
{

  // strong emphasis
  $text = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text);
  $text = preg_replace('/\*\*(.+?)\*\*/s'. '<strong>$1</strong>', $text);

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

function markdown_html_out($text)
{
  echo markdown_html($text);
}