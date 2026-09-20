<?php

/**
 * Function medialib link
 * generate link media location
 *
 * @param string $media_type
 * @param string $media_filename
 * @return string
 *
 */
function medialib_link($media_type, $media_filename)
{

    if (!preg_match('/^(?:[a-z0-9_-]|\.(?!\.))+$/iD', $media_filename)) {
        scriptlog_error("Bad filename", E_USER_WARNING);
    }

    if ($media_type === "application/pdf") {
        return app_url() . DS . APP_DOCUMENT . rawurlencode(basename($media_filename));
    }

    $group = get_mime_group($media_type);

    switch ($group) {
        case 'image':
            $image_dir =  __DIR__ . '/../../' . APP_IMAGE_MEDIUM . 'medium_' . $media_filename;

            $file_basename = substr($media_filename, 0, strripos($media_filename, '.'));

            if (is_readable($image_dir)) {
                return app_url() . DS . APP_IMAGE_MEDIUM . 'medium_' . rawurlencode(basename($media_filename));
            }

            return app_url() . DS . APP_IMAGE_MEDIUM . 'medium_' . rawurlencode(basename($file_basename . '.webp'));

        case 'audio':
            return app_url() . DS . APP_AUDIO . rawurlencode(basename($media_filename));

        case 'video':
            return app_url() . DS . APP_VIDEO . rawurlencode(basename($media_filename));

        default:
            return "#";
    }
}
