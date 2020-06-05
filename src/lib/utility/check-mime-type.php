<?php
/**
 * Checking Mime Type 
 * 
 * @category function
 * @param array $accepted_type
 * @param array $tmp_name
 * @return bool
 * 
 */
function check_mime_type($accepted_type, $tmp_name)
{

 $mime_type = false;
 
 if (!empty($tmp_name)) {

   $file_info = new finfo(FILEINFO_MIME_TYPE);
   $file_content = file_get_contents($tmp_name);
   $mime_type = $file_info -> buffer($file_content);

 }
 
 $extension = array_search($mime_type, $accepted_type, true);

 if(false === $extension) {

    $mime_type = false;

 } else {

    $mime_type = true;
 }

 return $mime_type;

}