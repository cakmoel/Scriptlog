<?php
/**
 * Function current_load_url
 * 
 * @category function
 * @see https://webcheatsheet.com/php/get_current_page_url.php
 * @return string
 * 
 */
function current_load_url()
{
    $pageURL = 'http';

    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
        
        $pageURL .= "s";
        
    }

    $pageURL .= "://";
    
    if ($_SERVER["SERVER_PORT"] != "80") {
     
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    
    } else {
     
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }

    return $pageURL;
    
}