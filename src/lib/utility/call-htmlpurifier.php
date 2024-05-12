<?php
/**
 * call_htmlpurifier
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 * 
 */
function call_htmlpurifier()
{
  
 $call_htmlpurifier = require __DIR__ . '/../../lib/core/HTMLPurifier.auto.php';
 return $call_htmlpurifier;
}