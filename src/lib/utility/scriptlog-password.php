<?php
/**
 * Scriptlog Password
 * Encrypt user password
 *  
 * @param string $password
 * @param integer $id
 * @return string
 * 
 */
function scriptlog_password($password)
{
    
 return password_hash(base64_encode(hash('sha384', $password, true)), PASSWORD_DEFAULT);
   
}