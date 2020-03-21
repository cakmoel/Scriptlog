<?php
/**
 * block request type function
 *
 * @param string $current_request
 * @return void
 * 
 */
function block_request_type($current_request)
{

 $block = false;

 $allowed_request = ['GET', 'POST'];

 if (!in_array($current_request, $allowed_request)) {

     $block = true;

 } else {

     $block = false;

 }

 return $block;
 
}

