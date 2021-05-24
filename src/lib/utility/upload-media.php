<?php
/**
 * upload_media
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $field_name
 * @param bool $check_image
 * @param bool $random_name
 * 
 */
function upload_media($file_location, $file_type, $file_size, $file_name)
{
 
  switch ($file_type) {

     case 'application/pdf' :
     case 'application/msword':
     case 'application/vnd.ms-excel' :
     case 'application/rtf':
     case 'application/vnd.ms-powerpoint':
     case 'application/rar':
     case 'application/zip':
     case 'application/x-zip':
     case 'multipart/x-zip':
     case 'application/x-zip-compressed': 
     case 'application/vnd.microsoft.portable-executable':
     case 'application/vnd.oasis.opendocument.text': 
     case 'application/vnd.oasis.opendocument.spreadsheet':
     
       upload_doc($file_location, $file_size, $file_type, $file_name);
   
       break;

     case 'audio/mpeg':
     case 'audio/wav' :
     case 'audio/ogg' :

       upload_audio($file_location, $file_name);

       break;
      
     case 'image/jpeg' :
     case 'image/pjpeg':
     case 'image/jpg':
     case 'image/png'  :
     case 'image/gif'  :
     case 'image/webp' :
     case 'image/bmp'  :
    
       upload_photo($file_location, $file_size, $file_type, $file_name);

       break;

     case 'video/mp4':
     case 'video/webm':
     case 'video/ogg':
     case 'video/mpeg':
      
       upload_video($file_location, $file_name);
       
       break;

     default:
  
       scriptlog_error("Error - file type not allowed!", E_USER_WARNING);
        
       break;

 }

}