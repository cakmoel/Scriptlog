<?php
/**
 * Function invoke image uploaded
 * to retrieve image uploaded from it directory
 * 
 * @category function 
 * @param string $media_type
 * @param string $media_filename
 * @return false not readable | true image exists and readable 
 * 
 */
function invoke_image_uploaded($media_filename, $image_thumb = true)
{

  
   $image_dir =  __DIR__ . '/../../'.APP_IMAGE_SMALL.'small_'.$media_filename;
   
   $image_src = null;

   if (is_readable($image_dir)) {

       if ($image_thumb) {

         $image_src = app_url().DS.APP_IMAGE_SMALL.'small_'.rawurlencode(basename($media_filename));
          
       } else {

         $image_src = app_url().DS.APP_IMAGE_LARGE.'large_'.rawurlencode(basename($media_filename));
          
       }

       return $image_src;

   } else {

      return false;
      
   }
  
}
