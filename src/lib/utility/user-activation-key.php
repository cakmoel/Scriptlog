<?php
/**
 * user_activation_key()
 * 
 * generating user activation key
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $value
 * @return string
 * 
 */
function user_activation_key($value)
{
    $salt = simple_salt(64);
    $token = sha1( mt_rand( 10000, 99999 ) . time() . $value . $salt);
    return $token;
}