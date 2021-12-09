<?php
/**
 * scriptlog_upload_filesize
 * Determine upload max filesize for theme and plugin
 * 
 * @category description
 * @return void
 * 
 */
function scriptlog_upload_filesize()
{

$current_upload_filesize = ini_get('upload_max_filesize');

$theme_size = null;

if (check_upload_filesize()) {

    $theme_size = (intval($current_upload_filesize) * 10485760)/10.29;

} else {

    $theme_size = APP_FILE_SIZE;

}

return $theme_size;

}