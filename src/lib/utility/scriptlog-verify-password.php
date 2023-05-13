<?php
/**
 * Scriptlog verify password
 * 
 * @param string $user_input The user supplied string
 * @param string $stored The string of known length to compare against
 * @return string
 * 
 */
function scriptlog_verify_password($user_input, $stored)
{

  return password_verify(base64_encode(hash('sha384', $user_input, true)), $stored);
    
}