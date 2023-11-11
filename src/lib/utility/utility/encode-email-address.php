<?php
/**
 * encode_email()
 * 
 * Encoding an email address to display on webpage
 * 
 * @param string $email
 * @see https://dzone.com/articles/encode-email-addresses-php
 * @return string
 */
function encode_email_address($email)
{

 $output = '';

 for ($i = 0; $i < strlen($email); $i++) {
    $output .= '&#' . ord($email[$i]) . ';';
 }

 return $output;

}
