<?php
/**
 * check_upload_filesize()
 * 
 * checking upload_max_filesize compared with current file uploaded
 * return true if current file uploaded larger than php.ini -- upload_max_filesize
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return boolean
 * 
 */
function check_upload_filesize()
{
 $current_upload_filesize = ini_get('upload_max_filesize');
 return ($current_upload_filesize > format_size_unit(APP_FILE_SIZE)) ? true : false;
}