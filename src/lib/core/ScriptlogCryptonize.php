<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class ScriptlogCryptonize
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */
use Laminas\Crypt\BlockCipher;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class ScriptlogCryptonize
{

const METHOD = 'AES-256-CBC';

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

  $cipher = BlockCipher::factory('openssl', array('algo' => 'aes'));

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

$ciphertext = Crypto::encrypt($message, $key);

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
public static function encryptAES($plaintext, $key)
{

 // Set a random salt
 if (function_exists("random_bytes")) {

  $iv = random_bytes(16);

} elseif (function_exists("openssl_random_pseudo_bytes")) {

  $iv = openssl_random_pseudo_bytes(16);

} else {

  $iv = simple_salt(16);

}

// Encryption
$ciphertext = openssl_encrypt($plaintext, self::METHOD, mb_substr($key, 0, 32, '8bit'), OPENSSL_RAW_DATA, $iv);

// Authentication
$hmac = hash_hmac('SHA256', $iv.$ciphertext, mb_substr($key, 32, null, '8bit'), true);

return $hmac . $iv . $ciphertext;

}

/**
 * decryptAES
 *
 * @param string $data
 * @param string $key
 * @return string
 * 
 */
public static function decryptAES($ciphertext, $key)
{
 
try {

  $hmac   = mb_substr($ciphertext, 0, 32, '8bit');
  $iv     = mb_substr($ciphertext, 32, 16, '8bit');
  $cipher = mb_substr($ciphertext, 48, null, '8bit');

  // Authentication
  $hmac_new = hash_hmac('SHA256', $iv . $cipher, mb_substr($key, 32, null, '8bit'), true);

  if (!hash_equals($hmac, $hmac_new)) {
    
    http_response_code(500);
    throw new ScriptlogCryptonizeException("Invalid ciphertext");

  }

  // Decrypt
  return openssl_decrypt($cipher, self::METHOD, mb_substr($key, 0, 32, '8bit'), OPENSSL_RAW_DATA, $iv);
 
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
 * @return string
 * 
 */
public static function decipherMessage($ciphertext, $key)
{

$cipher = BlockCipher::factory('openssl', array('algo' => 'aes'));

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

$plaintext = Crypto::decrypt($ciphertext, $key);
 
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

if ( file_exists(__DIR__ . '/../../lib/utility/.lts/lts.txt')) {

  $key_ascii = file_get_contents(__DIR__ . '/../../lib/utility/.lts/lts.txt');

} else {

  $keyObject = Key::createNewRandomKey();

  $key_ascii = $keyObject->saveToAsciiSafeString();

}

$loaded = Key::loadFromAsciiSafeString($key_ascii);

return $loaded;

}

/**
 * defaultSecretKey
 *
 * @return string
 * 
 */
private static function defaultSecretKey()
{

 if (function_exists("random_bytes")) {

   $key = random_bytes(16);
       
 } elseif(function_exists("openssl_random_pseudo_bytes")) {

   $key = openssl_random_pseudo_bytes(16);

 } else {

   $key = ircmaxell_random_generator(16);

 }

 return $key;

}

}