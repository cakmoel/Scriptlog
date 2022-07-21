<?php
/**
 * invoke_video_uploaded function
 *
 * @category function
 * @param string $media_filename
 * @return string | bool false if file not readable
 * 
 */
function invoke_video_uploaded($media_filename)
{

 $video_dir = __DIR__ . '/../../public/files/video/'.$media_filename;

 $video_src = null;

 if (is_readable($video_dir)) {

     $video_src = app_url().DS.APP_VIDEO.rawurlencode(basename($media_filename));

     return $video_src;

 } else {

    return false;

 }

}