<?php
/**
 * Function upload_video
 * 
 * @category uploading video
 * @param string $file_location
 * @param number $file_size
 */
function upload_video($file_location, $file_size, $file_type, $file_name)
{
   
   $video_path = __DIR__ . '/../../public/files/video/';
   $video_uploaded = $video_path . $file_name;
   
   if($file_size > APP_FILE_SIZE) {

      throw new UploadException("Error - file size too big!");

   } else {

      move_uploaded_file($file_location, $video_uploaded);

   }

}