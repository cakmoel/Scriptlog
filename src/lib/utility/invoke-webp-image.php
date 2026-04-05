<?php

/**
 * invoke_webp_image()
 * Returns WebP URL if available, otherwise returns original image URL
 *
 * @param string $media_filename
 * @param boolean $image_thumb
 * @return string
 */
function invoke_webp_image($media_filename, $image_thumb = true)
{
    if (empty($media_filename)) {
        return '';
    }

    $file_basename = substr($media_filename, 0, strripos($media_filename, '.'));

    $base_path = __DIR__ . '/../../public/files/pictures/';
    
    // Check for WebP in main folder
    $webp_file = $base_path . $file_basename . '.webp';
    $has_webp = is_readable($webp_file);

    if ($has_webp) {
        return app_url() . '/public/files/pictures/' . rawurlencode($file_basename . '.webp');
    }

    // No WebP, return sized or original JPG
    if ($image_thumb) {
        $medium_file = $base_path . 'medium/medium_' . basename($media_filename);
        if (is_readable($medium_file)) {
            return app_url() . '/public/files/pictures/medium/medium_' . rawurlencode(basename($media_filename));
        }
    } else {
        $large_file = $base_path . 'large/large_' . basename($media_filename);
        if (is_readable($large_file)) {
            return app_url() . '/public/files/pictures/large/large_' . rawurlencode(basename($media_filename));
        }
    }

    // Return original
    return app_url() . '/public/files/pictures/' . rawurlencode(basename($media_filename));
}
