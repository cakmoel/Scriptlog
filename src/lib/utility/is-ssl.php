<?php
/**
 * is_ssl function
 * a universal https detection method
 *
 * @see https://www.zigpress.com/detecting-https-in-php-the-definitive-guide/
 * @see https://stackoverflow.com/questions/7304182/detecting-ssl-with-php
 * @see https://stackoverflow.com/questions/1175096/how-to-find-out-if-youre-using-https-without-serverhttps
 * @see https://core.trac.wordpress.org/ticket/32354
 * @return boolean
 * 
 */
function is_ssl()
{

 if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
    return true;
 }
    
 if (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
    return true;
 }
    
 if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && ('https' == $_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    return true;
 }    
    
 return false;

}