<?php
/**
 * detect_origin
 * 
 * get origin of request with php
 * @see https://stackoverflow.com/questions/41326257/how-i-can-get-origin-of-request-with-php
 * @return string
 * 
 */
function detect_origin()
{

if (array_key_exists('HTTP_ORIGIN', $_SERVER)) {

    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "";

} elseif (array_key_exists('HTTP_REFERER', $_SERVER)) {
    
    $origin = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";

} else {

    $origin = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";

}

return $origin;

}