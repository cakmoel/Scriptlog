<?php
/**
 * check_file_extension
 *
 * @category Function
 * @author M.Noermoehammad
 * @param string $file_name
 * @license MIT
 * @version 1.0
 * @return bool
 * 
 */
function check_file_extension($file_name)
{

$extension = null;

if (function_exists('pathinfo')) {

$extension = pathinfo($file_name, PATHINFO_EXTENSION);

} elseif(class_exists('SplFileInfo')) {

$finfo = new SplFileInfo($file_name);
$extension = $finfo->getExtension();

} else {

$split = explode(".", $file_name);
    
$extension = (array_key_exists(1, $split) ? $split[1] : null);
      
}

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