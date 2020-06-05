<?php
/**
 * block request type function
 *
 * @param string $current_request
 * @return void
 * 
 */
function block_request_type($current_request, array $method_allowed)
{

 $block = false;

 if (!in_array($current_request, $method_allowed)) {

     $block = true;

 } else {

     $block = false;

 }

 return $block;
 
}

