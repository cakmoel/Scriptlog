<?php
/**
 * Upload Media Function
 * 
 * @param string $field_name
 * @param bool $check_image
 * @param bool $random_name
 * 
 */
function upload_media($field_name, $check_image = false, $random_name = false)
{
  
 if((!empty($_FILES[$field_name])) && ($_FILES[$field_name]['error'] == 0 )) {

    $file_info = pathinfo($_FILES[$field_name]['name']);
    $name = $file_info['filename'];
    $file_extension = $file_info['extension'];
    $tmp = str_replace(array('.',' '), array('',''), microtime());
    $new_filename = rename_file(md5($name.$tmp)).'-'.date('Ymd').'.'.$file_extension;

    if($random_name) {

      $tmp = str_replace(array('.',' '), array('',''), microtime());
      
      if(!$tmp || $tmp == '' ) {
         
         scriptlog_error("File must have a name", E_USER_NOTICE);

		}
		// generate random filename
		$new_filename = rename_file(md5($name.$tmp)).'-'.date('Ymd').'.'.$ext;
		
    }

    $upload_time_path = date('Y').DS.date('m').DS.date('d').DS;
    $upload_path = isset($_SESSION['user_login']) ? $_SESSION['user_login'] : '';

    switch($file_extension) {

       case 'jpg':
       case 'jpeg':
       case 'gif':
       case 'png':

            $image_path = $upload_path . DIRECTORY_SEPARATOR . $upload_time_path . $new_filename;

            upload_photo($field_name, 770, 400, 'crop', $image_path);

         break;

    }
    

 } else {

    scriptlog_error("No file uploaded", E_USER_NOTICE);

 }

}