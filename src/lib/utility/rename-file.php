<?php
/**
 * Rename File Function
 * 
 * @param mixed $filename
 * @return mixed
 * 
 */
function rename_file($filename)
{
  return preg_replace('/\s+/', '_', basename($filename));
}