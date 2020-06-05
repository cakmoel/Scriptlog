<?php
/**
 * Function Call HTMLPurifier
 * 
 * @category function
 * @return string
 * 
 */
function call_htmlpurifier()
{
  
 $call_htmlpurifier = require __DIR__ . '/../../lib/core/HTMLPurifier.auto.php';

 return $call_htmlpurifier;

}