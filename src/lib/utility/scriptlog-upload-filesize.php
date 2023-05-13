<?php
/**
 * scriptlog_upload_filesize
 * Determine upload max filesize for theme and plugin
 * 
 * @category Function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return numeric|int
 * 
 */
function scriptlog_upload_filesize()
{

$current_upload_filesize = ini_get('upload_max_filesize');

$file_size = null;

if (check_upload_filesize()) {

    $file_size = (intval($current_upload_filesize) * 10485760)/10.29;

} else {

    $file_size = APP_FILE_SIZE;

}

return $file_size;

}