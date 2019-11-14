<?php
/**
 * Function current_load_page
 * 
 * @category function
 * @package  SCRIPTLOG/LIB/UTILITY
 * @see https://webcheatsheet.com/php/get_current_page_url.php
 * @return string
 * 
 */
function current_load_page()
{
    return substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
}