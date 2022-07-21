<?php
/**
 * block_request_type
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $current_request
 * @return bool
 * 
 */
function block_request_type($current_request, array $method_allowed)
{

 $block = true;

 if (!in_array($current_request, $method_allowed)) {

    $block = true;

 } else {

    $block = false;

 }

 return $block;
 
}

