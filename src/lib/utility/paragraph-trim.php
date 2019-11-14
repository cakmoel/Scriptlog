<?php
/**
 * Paragraph trim function
 * trimming post content
 * Example: nl2br(htmlspecialchars(paragraph_trim($post_content)))
 * 
 * @category Function
 * @package SCRIPTLOG/LIB/UTILITY
 * @param string $content
 * @param integer|number $limit
 * @param string $schr
 * @param string $scnt
 * 
 */
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