<?php
/**
 * upload_video()
 * 
 * uploading video file
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $file_location
 * @param string $file_size
 * @param string $file_type
 * @param string $file_name
 * @return bool
 * 
 */
function upload_video($file_location, $file_name)
{
   
   $video_path = __DIR__ . '/../../'.APP_VIDEO;
   $video_uploaded = $video_path . $file_name;
   
   if(!move_uploaded_file($file_location, $video_uploaded)) {

      scriptlog_error("Video uploaded failure");

   } 

}