<?php
/**
 * Function check uploaded filename
 * 
 * @category function
 * @see https://www.php.net/manual/en/function.move-uploaded-file.php#111412
 * @param string $filename
 * @return bool
 * 
 */
function check_file_name($filename)
{
  return ((preg_match("`^[-0-9A-Z_\.]+$`i", basename($filename)) ? true : false));
}