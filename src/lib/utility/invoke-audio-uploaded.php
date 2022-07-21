<?php 
/**
 * Invoke audio uploaded function
 *
 * @category function
 * @param string $media_filename
 * @return bool false if directory not readable
 * 
 */
function invoke_audio_uploaded($media_filename)
{

$audio_dir = __DIR__ . '/../../public/files/audio/'.$media_filename;

$audio_src = null;

if (is_readable($audio_dir)) {

    $audio_src = app_url().DS.APP_AUDIO.rawurlencode(basename($media_filename));

    return $audio_src;

} else {

   return false;

}

}