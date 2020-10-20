<?php
/**
 * Class ScriptlogCrypto
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */

use Zend\Crypt\BlockCipher;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class ScriptlogCryptonize
{

const METHOD = 'AES-128-CBC';

/**
 * generateSecretKey
 *
 * @return string
 * 
 */
public static function generateSecretKey()
{

  return self::defaultSecretKey();
   
}

/**
 * cipherMessage
 *
 * @param string $message
 * @param string $key
 * @return string
 * 
 */
public static function cipherMessage($message, $key)
{

  $cipher = \Zend\Crypt\BlockCipher::factory('openssl', array('algo' => 'aes'));

  $cipher->setKey($key);

  $ciphertext = $cipher->encrypt($message);

  return $ciphertext;

}

/**
 * scriptlogCipher
 *
 * @param string $message
 * @param string $key
 * @return string
 * 
 */
public static function scriptlogCipher($message, $key)
{

$ciphertext = \Defuse\Crypto\Crypto::encrypt($message, $key);

return $ciphertext;

}

/**
 * encryptAES
 * 
 * @param string $data
 * @param string $key
 * @return string
 * 
 */
public static function encryptAES($plaintext, $password)
{

 // Set a random salt
 if (function_exists("random_bytes")) {

  $iv = random_bytes(16);

} elseif (function_exists("openssl_random_pseudo_bytes")) {

  $iv = openssl_random_pseudo_bytes(16);

} else {

  $iv = simple_salt(16);

}

$key = hash('sha256', $password, true);

$ciphertext = openssl_encrypt($plaintext, self::METHOD, $key, OPENSSL_RAW_DATA, $iv);

$hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);

return $iv . $hash . $ciphertext;

}

/**
 * decryptAES
 *
 * @param string $data
 * @param string $key
 * @return string
 * 
 */
public static function decryptAES($ciphertext, $password)
{
 
try {

  $iv = substr($ciphertext, 0, 16);
  
  $hash = substr($ciphertext, 16, 32);
  
  $ciphertext = substr($ciphertext, 48);
  
  $key = hash('sha256', $password, true);

  if (!hash_equals(hash_hmac('sha256', $ciphertext . $iv, $key, true), $hash)) {

    http_response_code(500);
    throw new ScriptlogCryptonizeException("Invalid ciphertext");

  }

  return openssl_decrypt($ciphertext, self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
   
} catch (ScriptlogCryptonizeException $e) {
   
   LogError::setStatusCode(http_response_code());
   LogError::newMessage($e);
   LogError::customErrorMessage('admin');

}

}

/**
 * decipherMessage
 *
 * @param string $ciphertext
 * @param string $key
 * @return void
 * 
 */
public static function decipherMessage($ciphertext, $key)
{

$cipher = \Zend\Crypt\BlockCipher::factory('openssl', array('algo' => 'aes'));

$cipher->setKey($key);
 
$result = $cipher->decrypt($ciphertext);
 
return $result;

}

/**
 * scriptlogDecipher
 *
 * @param string $ciphertext
 * @param string $key
 * @return string
 * 
 */
public static function scriptlogDecipher($ciphertext, $key)
{

$plaintext = \Defuse\Crypto\Crypto::decrypt($ciphertext, $key);
 
return $plaintext;

}

/**
 * scriptlogCipherKey
 *
 * @return string
 * 
 */
public static function scriptlogCipherKey()
{

$key_ascii = file_get_contents(__DIR__ . '/../lib/utility/.lts/lts.txt');

$loaded = \Defuse\Crypto\Key::loadFromAsciiSafeString($key_ascii);

return $loaded;

}

/**
 * defaultSecretKey
 *
 * @return string
 * 
 */
protected static function defaultSecretKey()
{

 if (function_exists("random_bytes")) {

   $key = random_bytes(64);
       
 } elseif(function_exists("openssl_random_pseudo_bytes")) {

   $key = openssl_random_pseudo_bytes(64);

 } else {

   $key = ircmaxell_random_compat();

 }

 return $key;

}

}