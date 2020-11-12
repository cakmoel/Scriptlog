<?php
/**
 * Upload file
 * 
 * @param string $filename
 * @param string $folder
 * 
 */
function upload_doc($file_location, $file_name)
{
  // Doc directory
  $doc_path = __DIR__ . '/../../'.APP_DOCUMENT;
  $doc_uploaded = $doc_path . $file_name;

  if (get_file_extension($file_name) == 'zip') {

      if ((zip_file_scanner($file_name) === true) || (format_size_unit(get_zip_size($file_location)) == '0 bytes')) {

          scriptlog_error("Document corrupted!");

      }

  }

  if (!move_uploaded_file($file_location, $doc_uploaded)) {

    scriptlog_error("upload doc failed!");

  }

}