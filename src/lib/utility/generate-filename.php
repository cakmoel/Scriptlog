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
 
 $file_info = pathinfo($file_name);
 $name = $file_info['filename'];
 $file_extension = $file_info['extension'];
 $tmp = str_replace(array('.',' '), array('',''), microtime());
 $new_filename = rename_file(md5($name.$tmp)).'-'.date('Ymd').'.'.$file_extension;

 return (array('new_filename'=>$new_filename, 'file_extension'=>$file_extension));

}