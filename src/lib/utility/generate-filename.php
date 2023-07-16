<?php
/**
 * Generate new filename function
 *
 * @param string $file_name
 * @return string
 * 
 */
function generate_filename($file_name)
{
 
// get filename
$file_basename = substr($file_name, 0, strripos($file_name, '.'));
    
// get file extension
$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
    
$new_filename = rename_file(md5(generate_hash(13).$file_basename)).'.'.$file_ext;

return ['new_filename' => $new_filename, 'file_extension' => $file_ext ];

}