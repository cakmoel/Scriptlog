<?php 
/**
 * invoke_frontimg()
 *
 * @category function
 * @author M.Noermoehammad
 * @param string $media_filename
 * @param boolean $image_thumb
 * 
 */
function invoke_frontimg($media_filename, $image_thumb = true)
{

    $file_basename = substr($media_filename, 0, strripos($media_filename, '.'));
    
    if ($file_basename === 'nophoto') {

        return app_url().DS.APP_IMAGE.rawurlencode(basename($file_basename.'.jpg')); 
        
    } else {

        return invoke_webp_image($media_filename, $image_thumb);

    }

}