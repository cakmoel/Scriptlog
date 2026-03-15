<?php
/**
 * generate_token()
 * 
 * this will generate a random token string
 * 
 * @category Function
 * @param number $length
 * @return string
 * 
 */
function generate_token($length = 20)
{
    
 if (function_exists("random_bytes")) {
        
    return bin2hex(random_bytes($length));
        
 } elseif (function_exists("openssl_random_pseudo_bytes")) {
        
    return bin2hex(openssl_random_pseudo_bytes($length));
        
 } else {
        
    return bin2hex(ircmaxell_random_generator($length));
        
 }
    
}