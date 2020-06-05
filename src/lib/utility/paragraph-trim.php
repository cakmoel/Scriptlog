<?php
/**
 * the collection of paragraph function
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
  
  if(version_compare(phpversion(), '5.6.40', '<')) {

      return str_replace(paragraph_newline_checker(), '<br>', $content);

  }

  return nl2br(htmlspecialchars(paragraph_trim($content)), false);

}

function paragraph_trim($content, $limit = 500, $schr="\n", $scnt=2)
{
  $post = 0;
  $trimmed = false;
  for($i = 1; $i <= $scnt; $i++) {

    if($tmp = strpos($content, $schr, $post+1)) {
        $post = $tmp;
        $trimmed = true;
    } else {
        $post = strlen($content) - 1;
        $trimmed = false;
        break;
    }

  }

  $content = substr($content, 0, $post);

  if(strlen($content) > $limit) {
    $content = substr($content, 0, $limit);
    $content = substr($content, 0, strrpos($content,' '));
    $trimmed = true;
  }

  if($trimmed) $content .= '...';

  return $content;

}

function paragraph_newline_checker()
{
  if(defined('PHP_EOL')) {

      return PHP_EOL;

  }

  $new_line = "\r\n";

  if(isset($_SERVER["HTTP_USER_AGENT"]) && strstr(strtolower($_SERVER["HTTP_USER_AGENT"]), 'win')) {

     $new_line = "\r\n";

  } elseif(isset($_SERVER["HTTP_USER_AGENT"]) && strstr(strtolower($_SERVER["HTTP_USER_AGENT"]), 'mac')) {

     $new_line = "\r";

  } else {

      $new_line = "\n";

  }

  return $new_line;

}