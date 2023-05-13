<?php
/**
 * generate_session_key()
 * 
 * generating session key
 * 
 * @category Function
 * @author M.Noermoehammad
 * @license MIT
 * @param string $value
 * @return string
 * 
 */
function generate_session_key($value, $length)
{
    
 $salt = simple_salt($length);

 $sessionKey = hash_hmac('sha384', $value, hash('sha384', app_key().$salt, true));

 Session::getInstance()->scriptlog_user_session = $sessionKey;
 
 return $sessionKey;
    
}