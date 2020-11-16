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

  if (move_uploaded_file($file_location, $doc_uploaded)) {

    if ( get_file_extension($file_name) == 'zip') {

      if ((zip_file_scanner($doc_uploaded) === true) || (format_size_unit(get_zip_size($file_location)) == '0 bytes')) {

          unlink($doc_uploaded);

      } else {

         $zip = new ZipArchive();

         $opened = $zip->open($doc_uploaded);

         if ( $opened === true ) {

            $file_count = (version_compare(phpversion(), "7.2.0", ">=")) ? $zip->count() : $zip->numFiles;

            for ($i = 0; $i < $file_count; $i++) {

               $file_index = $zip->getNameIndex($i);

               preg_match('/(.*)(phpinfo|system|php_uname|chmod|fopen|eval|flclose|readfile|base64_decode|passthru)(.*)/Us', $file_index, $matches);

               if (count($matches) > 0) {

                    $zip->deleteName($file_index);

               }
            
            }

            $zip->close();
           
          }
      }

    }

  }
  

}