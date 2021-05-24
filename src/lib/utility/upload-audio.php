<?php
/**
 * upload_audio function
 *
 * @param string $file_location
 * @param string $file_size
 * @param string $file_type
 * @param string $file_name
 * @category function
 * @return bool
 * 
 */
function upload_audio($file_location, $file_name)
{
  
  $audio_path = __DIR__ . '/../../'. APP_AUDIO;
  $audio_uploaded = $audio_path . $file_name;
  
  if (!move_uploaded_file($file_location, $audio_uploaded)) {

   scriptlog_error("Audio file uploaded failure");

  }
  
}