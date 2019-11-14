<?php
/**
 * Invoke Post Function
 * 
 * @category Function
 * @package  SCRIPTLOG/LIB/UTILITY
 * @param integer $args
 * 
 */
function invoke_post($frontPaginator, $sanitizer, $args = null)
{
  
  $errors = array();

  $postDao = new Post();

  $content =  new ContentGateway($frontPaginator, $sanitizer);
  
  $frontContent = new FrontContent();

  if (is_null($args)) {


    
  } else {

    $detail_post = $frontContent -> readPost($postDao, $args);
    
    if ($detail_post === false) {
      
      $errors[] = 'Post requested not found';

    }

  }

}