<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Invoke audio uploaded function
 *
 * @category function
 * @param string $media_filename
 * @return string|false false if directory not readable
 *
 */
function invoke_audio_uploaded($media_filename)
{

    $audio_dir = __DIR__ . '/../../public/files/audio/' . $media_filename;

    if (is_readable($audio_dir)) {
        return app_url() . DS . APP_AUDIO . rawurlencode(basename($media_filename));
    } else {
        return false;
    }
}
