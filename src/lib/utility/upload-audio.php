<?php

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