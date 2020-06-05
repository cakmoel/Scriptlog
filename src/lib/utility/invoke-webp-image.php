<?php
/**
 * Function invoke webp image format
 *
 * @param [type] $media_filename
 * @param boolean $image_thumb
 * @return string | false if no string returned
 * 
 */
function invoke_webp_image($media_filename, $image_thumb = true) 
{

$image_dir =  __DIR__ . '/../../public/files/pictures/thumbs/small_'.$media_filename;

$file_basename = substr($media_filename, 0, strripos($media_filename, '.'));

$image_src = null;

if(is_readable($image_dir)) {

    if($image_thumb) {

        $image_src =  app_url().DS.APP_IMAGE_THUMB.'small_'.rawurlencode($file_basename.'.webp');

        return $image_src;

    } else {

        $image_src = app_url().DS.APP_IMAGE.rawurlencode(basename($file_basename.'.webp'));

        return $image_src;

    }

} else {

    return false;

}

}