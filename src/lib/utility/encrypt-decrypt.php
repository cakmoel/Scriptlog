<?php
/**
 * create_encoded_key
 * 
 * @category Funtion create_encoded_key to make binary data
 * @license MIT
 * @version 1.0
 */
function create_encoded_key()
{
  return base64_encode(app_key());
}

/**
 * encrypt()
 * Encrypting message
 * 
 * @category Function ecnrypt() to encrypt message
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @see https://www.php.net/manual/en/function.openssl-encrypt.php
 * @param string $text
 * @param string $key
 * @return string
 */
function encrypt(string $data, string $key): string
{

 $passphrase = base64_decode(create_encoded_key());
 $second_passphrase = base64_decode($key);

 $cipher_method = 'aes-256-cbc';

 if (! in_array($cipher_method, openssl_get_cipher_methods())) {
   scriptlog_error("Cipher method not supported");
 }

 $iv_length = openssl_cipher_iv_length($cipher_method);

 if (function_exists('random_bytes')) {

    $iv = random_bytes($iv_length);

 } elseif ('openssl_random_pseudo_bytes') {

    $iv = openssl_random_pseudo_bytes($iv_length);

 } else {

    $iv = ircmaxell_random_generator($iv_length);

 }

 $ciphertext = openssl_encrypt($data, $cipher_method, $passphrase, OPENSSL_RAW_DATA, $iv);
 
 if (! in_array('sha3-512', hash_algos())) {

   scriptlog_error("Hashing algorithm is not supported");

 } else {

   $hmac = hash_hmac('sha3-512', $ciphertext, $second_passphrase, true);

 }
  
 return base64_encode($iv.$hmac.$ciphertext);
 
}

/**
 * decrypt()
 * Decrypting message
 * 
 * @category Function decrypt() to decrypt message
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @see https://www.php.net/manual/en/function.openssl-decrypt.php
 * @param string $text
 * @param string $key
 * @return string
 */
function decrypt(string $data, string $key): string
{
 $passphrase = base64_decode(create_encoded_key());
 $second_passphrase = base64_decode($key);
 $data_decoded = base64_decode($data);

 $cipher_method = 'aes-256-cbc';

 if (! in_array($cipher_method, openssl_get_cipher_methods())) {

   scriptlog_error("Cipher method not supported");

 }

 $iv_length = openssl_cipher_iv_length($cipher_method);
 $iv =  substr($data_decoded, 0, $iv_length);

 $encrypt_key = substr($data_decoded, $iv_length, 64);
 $hmac_key = substr($data_decoded, $iv_length + 64);

 $data_decrypted = openssl_decrypt($hmac_key, $cipher_method, $passphrase, OPENSSL_RAW_DATA, $iv);

 if (! in_array('sha3-512', hash_algos())) {
    scriptlog_error("Hashing algorithm is not supported");
 } else {

    $hmac_key_new = hash_hmac('sha3-512', $hmac_key, $second_passphrase, true);
 }
 
 return (hash_equals($encrypt_key, $hmac_key_new)) ? $data_decrypted : false;

}