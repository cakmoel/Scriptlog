<?php
/**
 * check_upload_filesize
 *
 * @category Function
 * @return void
 * 
 */
function check_upload_filesize()
{

$current_upload_filesize = ini_get('upload_max_filesize');

if ($current_upload_filesize > format_size_unit(APP_FILE_SIZE)) {

    return true;

} else {

    return false;
}

}