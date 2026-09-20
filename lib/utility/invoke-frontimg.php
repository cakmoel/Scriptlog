<?php

/**
 * invoke_frontimg()
 *
 * @param string $media_filename
 * @param boolean $image_thumb
 * @return string
 */
function invoke_frontimg($media_filename, $image_thumb = true)
{
    if (empty($media_filename)) {
        return '';
    }

    $file_basename = substr($media_filename, 0, strripos($media_filename, '.'));

    if ($file_basename === 'nophoto') {
        return app_url() . '/public/files/pictures/nophoto.jpg';
    }

    return invoke_webp_image($media_filename, $image_thumb);
}
