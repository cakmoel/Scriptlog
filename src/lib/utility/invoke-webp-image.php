<?php
/**
 * Function invoke webp image format
 *
 * @category functions
 * @author M.Noermoehammad
 * @license MIT
 * @param string $media_filename
 * @param boolean $image_thumb
 * @return string | false if no string returned
 * 
 */
function invoke_webp_image($media_filename, $image_thumb = true) 
{

$file_basename = substr($media_filename, 0, strripos($media_filename, '.'));

$image_dir =  __DIR__ . '/../../'.APP_IMAGE_SMALL.'small_'.$file_basename.'.webp';

$image_src = null;

if(is_readable($image_dir)) {

    if($image_thumb) {

        $image_src =  app_url().DS.APP_IMAGE_SMALL.'small_'.rawurlencode($file_basename.'.webp');
        
    } else {

        $image_src = app_url().DS.APP_IMAGE_LARGE.'large_'.rawurlencode(basename($file_basename.'.webp'));
        
    }

    return $image_src;

} else {

    return false;

}

}