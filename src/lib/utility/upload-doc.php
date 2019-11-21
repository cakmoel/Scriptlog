<?php
/**
 * Upload file
 * 
 * @param string $filename
 * @param string $folder
 * 
 */
function upload_doc($file_location, $file_size, $file_type, $file_name)
{
  // Doc directory
  $doc_path = __DIR__ . '/../../public/files/docs/';
  $doc_uploaded = $doc_path . $file_name;

  if ($file_size > APP_FILE_SIZE) {

     throw new UploadException("Error - File size too big!");

  } else {

     move_uploaded_file($file_location, $doc_uploaded);
     
  }
  
}