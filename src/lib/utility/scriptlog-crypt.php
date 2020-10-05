<?php

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

/**
 * scriptlog_cipher
 * 
 * @category Function
 * @param string $message
 * @param string $key
 * 
 */
function scriptlog_cipher($message, $key)
{
  
  $ciphertext = \Defuse\Crypto\Crypto::encrypt($message, $key);

  return $ciphertext;

}

/**
 * scriptlog_decipher
 *
 * @param string $ciphertext
 * @param string $key
 * @return string
 */
function scriptlog_decipher($ciphertext, $key)
{

 $plaintext = \Defuse\Crypto\Crypto::decrypt($ciphertext, $key);
 
 return $plaintext;

}

/**
 * scriptlog_cipher_key
 *
 * @return string
 * 
 */
function scriptlog_cipher_key()
{
  
  $key_ascii = file_get_contents(__DIR__ . '/.lts/lts.txt');

  $loaded = \Defuse\Crypto\Key::loadFromAsciiSafeString($key_ascii);

  return $loaded;

}