<?php
/**
 * Generate Session Key Function
 * 
 * @category Function
 * @param string $value
 * @return string
 */
function generate_session_key($value, $length)
{
    
    if (function_exists("random_bytes")) {

        $sessionKey = bin2hex(random_bytes($length).$value);

    } elseif (function_exists("openssl_random_pseudo_bytes")) {

        $sessionKey = bin2hex(openssl_random_pseudo_bytes($length).$value);

    } else {

        $sessionKey = sha1(mt_rand(100, 999).time().random_generator($length).$value);

    }

    return $sessionKey;
    
}