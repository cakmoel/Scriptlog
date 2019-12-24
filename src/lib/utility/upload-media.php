<?php
/**
 * Upload Media Function
 * 
 * @param string $field_name
 * @param bool $check_image
 * @param bool $random_name
 * 
 */
function upload_media($file_location, $file_type, $file_size, $file_name)
{
 
  $errors = array();
  $checkError = true;

  switch ($file_type) {

     case 'application/pdf' :
     case 'application/msword':
     case 'application/vnd.ms-excel' :
     case 'application/octet-stream' :
     case 'application/vnd.ms-powerpoint':
     case 'application/rar':
     case 'application/zip':
     case 'application/vnd.microsoft.portable-executable':
     case 'application/vnd.oasis.opendocument.text': 
     
       upload_doc($file_size, $file_size, $file_type, $file_name);
   
       break;

     case 'audio/mpeg':
     case 'audio/wav':
     case 'audio/ogg' :

       upload_audio($file_size, $file_size, $file_type, $file_name);

       break;
      
     case 'image/jpeg' :
     case 'image/png'  :
     case 'image/gif'  :
     case 'image/webp' : 
     
       upload_photo($file_location, $file_size, $file_type, 770, 400, 'crop', $file_name);

       break;

     case 'video/mp4':
     case 'video/webm':
     case 'video/ogg':
      
       upload_video($file_location, $file_size, $file_type, $file_name);
       
       break;

     default:
  
       scriptlog_error("Error - file type not allowed!", E_USER_ERROR);
        
       break;

 }

}