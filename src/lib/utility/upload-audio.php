<?php
/**
 * upload_audio function
 *
 * @param string $file_location
 * @param string $file_size
 * @param string $file_type
 * @param string $file_name
 * @category function
 * @return void
 */
function upload_audio($file_location, $file_size, $file_type, $file_name)
{
  
  $audio_path = __DIR__ . '/../../public/files/audio/';
  $audio_uploaded = $audio_path . $file_name;

  if($file_size > APP_FILE_SIZE) {

    throw new UploadException("Error - File size too big!");

  } else {

    move_uploaded_file($file_location, $audio_uploaded);

  }
  
}