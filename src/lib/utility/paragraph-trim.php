<?php

/**
 * paragraph_l2br()
 * 
 * handle text on paragraph as a post content
 * Example: paragraph_l2br(htmlspecialchars(paragraph_trim($post_content)))
 * 
 * @category Function
 * @param string $content
 * @param integer|number $limit
 * @param string $schr
 * @param string $scnt
 * 
 */

function paragraph_l2br($content)
{
  if (version_compare(phpversion(), '7.4', '<=')) {

    return str_replace(paragraph_newline_checker(), '<br>', $content);

  }

  return nl2br($content, false);
  
}

/**
 * paragraph_trim()
 *
 * @param string $content
 * @param integer $limit
 * @param string $schr
 * @param integer $scnt
 * 
 */
function paragraph_trim($content, $limit = 200, $schr = PHP_EOL, $scnt = 2)
{

  $post = 0;
  $entry = null;
  $trimmed = false;

  for ($i = 1; $i <= $scnt; $i++) {

    if ($tmp = strpos($content, $schr, $post + 1)) {
      $post = $tmp;
      $trimmed = true;
    } else {
      $post = strlen($content) - 1;
      $trimmed = false;
      break;
    }
  }

  $entry = html_entity_decode($content);
  $entry = strip_tags($entry);
  $entry = purify_dirty_html($entry);
  $content = substr($entry, 0, $post);

  if (strlen($content) >= $limit) {

    
    $content = substr($entry, 0, $limit);
    $content = substr($entry, 0, strrpos($content, " "));
    $trimmed = true;

  }

  if ($trimmed) {
    $content .= "...";
  }

  return $content;
}

/**
 * paragraph_newline_checker
 *
 */
function paragraph_newline_checker()
{

  if (defined('PHP_EOL')) {

    return PHP_EOL;
  
  } else {

    if (isset($_SERVER["HTTP_USER_AGENT"]) && strstr(strtolower($_SERVER["HTTP_USER_AGENT"]), 'win')) {

      $new_line = "\r\n";
  
    } elseif (isset($_SERVER["HTTP_USER_AGENT"]) && strstr(strtolower($_SERVER["HTTP_USER_AGENT"]), 'mac')) {
  
      $new_line = "\r";
    } else {
  
      $new_line = "\n";
    }
  
    return $new_line;

  }

}
