<?php
/**
 * check_file_extension()
 *
 * checking file extension
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $file_name
 * @return bool
 * 
 */
function check_file_extension($file_name)
{

$extension = null;

$extension = get_file_extension($file_name);

switch (strtolower($extension)) {
    
// image 
case 'jpg':
case 'jpe':
case 'jpeg':
case 'png':
case 'gif':
case 'bmp':
case 'tif':
case 'tiff':
case 'webp':
case 'ico':

  return true;

  break;
    
// audio/video
case 'mp3':
case 'wav':
case 'ogg':
case 'mp4':
case 'webm':

  return true;

  break;

// archive
case 'zip':

  return true;

  break;

  // docs
case 'doc':
case 'rtf':
case 'xls':
case 'ppt':
case 'docx':
case 'xlsx':
case 'pptx':
case 'odt':
case 'ods':
case 'pdf':

 return true;

 break;

default:
        
   return false;

    break;

}

}