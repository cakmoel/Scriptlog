<?php
/**
 * Function check file length
 * 
 * @category Function
 * @see      https://www.php.net/manual/en/function.move-uploaded-file.php#111412
 * @param string $filename
 * @return bool
 * 
 */
function check_file_length($filename)
{
 return ((mb_strlen(basename($filename, "UTF-8") > 225) ? true : false));
}