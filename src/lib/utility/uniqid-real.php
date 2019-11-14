<?php
/**
 * Uniqid Real Function
 * to cretate randomm unique ID or Key
 * 
 * @param number $length
 * @throws Exception
 * @return string
 * 
 */
function uniqid_real($length = 13)
{
    // uniqid gives 13 chars, but you could adjust it to your needs.
    if (function_exists("random_bytes")) {

        $bytes = random_bytes(ceil($length / 2));

    } elseif (function_exists("openssl_random_pseudo_bytes")) {

        $bytes = openssl_random_pseudo_bytes(ceil($length / 2));

    } else {

        throw new Exception("no cryptographically secure random function available");

    }

    return substr(bin2hex($bytes), 0, $length);
    
}