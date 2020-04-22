<?php
/**
 * Function invoke image uploaded
 * 
 * @category function to retrieve image uploaded from it directory
 * @param string $media_type
 * @param string $media_filename
 * 
 */
function invoke_image_uploaded($media_filename, $image_thumb = true)
{

   $image_dir =  __DIR__ . '/../../public/files/pictures/thumbs/small_'.$media_filename;

   if (is_readable($image_dir)) {

       if ($image_thumb) {

          $image_src = app_url().APP_IMAGE.'thumbs/small_'.$media_filename;

          return $image_src;

       } else {

          $image_src = app_url().APP_IMAGE.rawurlencode(basename($media_filename));

          return $image_src;

       }

   } else {

      return false;
      
   }
  
}