<?php
/**
 * Checking Mime Type 
 * 
 * @category function
 * @author M.Noermoehammad
 * @param array $accepted_type
 * @param array $tmp_name
 * @license MIT
 * @version 1.0
 * @return bool
 * 
 */
function check_mime_type($accepted_type, $tmp_name)
{

 $mime_type = false;
  
 if (class_exists('finfo')) {

   $file_info = new finfo(FILEINFO_MIME_TYPE);
   $file_content = file_get_contents($tmp_name);
   $type = $file_info -> buffer($file_content);

 } elseif (function_exists('finfo_open')) {
    
   $finfo = finfo_open(FILEINFO_MIME_TYPE);
   $type = finfo_file($finfo, $tmp_name);
   finfo_close();

 } elseif (function_exists('mime_content_type')) {

   $type = mime_content_type($tmp_name);

 } else {

    $finfo = new SplFileInfo($tmp_name);
    $ext_info = $finfo->getExtension();
 
    if ($ext_info == 'jpg' || $ext_info == 'jpeg' || $ext_info == 'png' || $ext_info == 'gif' || $ext_info == 'tiff' || $ext_info == 'tif' || $ext_info == 'bmp' || $ext_info == 'webp' || $ext_info == 'ico') {

      $allowed_image_type = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM, IMAGETYPE_BMP, IMAGETYPE_WEBP, IMAGETYPE_ICO);
      $detected_image_type = exif_imagetype($tmp_name);
     
      if (!in_array($detected_image_type, $allowed_image_type)) {
         
          scriptlog_error("Image file type not allowed");

      } 
      
      $type = getimage_type($tmp_name);

    } else {

       $type = get_mime($tmp_name);

    }

 }
 
 $extension = array_search($type, $accepted_type, true);

 if(false === $extension) {

    $mime_type = false;

 } else {

    $mime_type = true;
 }

 return $mime_type;

}