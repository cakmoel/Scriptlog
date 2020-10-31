<?php
/**
 * class Tokenizer
 * 
 * implementing minimum requirements for secure user authentication in scriptlog
 * with long-term persistence - login with "Remember Me" Cookies
 *
 * @category Core Class
 * 
 * @see https://github.com/ircmaxell/RandomLib
 * @see https://stackoverflow.com/questions/1846202/php-how-to-generate-a-random-unique-alphanumeric-string
 * @see https://phppot.com/php/secure-remember-me-for-login-using-php-session-and-cookies/
 * @see https://paragonie.com/blog/2015/05/using-encryption-and-authentication-correctly
 * @see https://gist.github.com/wopot/94e33bdd1d7faaaa56e2
 * @see https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
 * @see https://paragonie.com/blog/2015/11/choosing-right-cryptography-library-for-your-php-project-guide
 * @see https://www.zimuel.it/blog/cryptography-made-easy-with-zend-framework
 * 
 * @uses \RandomLib\Factory::getMediumStrengthGenerator
 * @uses ScriptlogCryptonize::cipherMessage
 * @uses ScriptlogCryptonize::decipherMessage
 * 
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * 
 */
final class  Tokenizer
{

/**
 * Create token
 * 
 * @method public createToken()
 * @static
 * @param integer|number $length
 * @return string
 * 
 */
  public static function createToken($length)
  {
     
    $generator = (new \RandomLib\Factory())->getMediumStrengthGenerator();
    
    $token = $generator->generateString($length);

    return $token;

  }

/**
 * setRandomPasswordProtected
 *
 * @param string $password
 * @param string $key
 * @return void
 * 
 */
  public static function setRandomPasswordProtected($password, $key)
  {

    $hash_data = hash('SHA256', $password, true);

    $hash_encoded = base64_encode($hash_data);

    $cipher_data = password_hash($hash_encoded, PASSWORD_DEFAULT);

    return ScriptlogCryptonize::cipherMessage($cipher_data, $key);

  }

/**
 * getRandomPasswordProtected
 *
 * @param string $cipher_data
 * @param string $password
 * @param string $key
 * @return void
 * 
 */
  public static function getRandomPasswordProtected($cipher_data, $password, $key)
  {

    $decipher = ScriptlogCryptonize::decipherMessage($cipher_data, $key);

    $hash_data = hash('SHA256', $password, true);

    $hash_encoded = base64_encode($hash_data);

    return password_verify($hash_encoded, $decipher);

  }

/**
 * setRandomSelectorProtected
 *
 * @param string $selector
 * @param string $key
 * @return void
 * 
 */
  public static function setRandomSelectorProtected($selector, $key)
  {

    $hash_data = hash('SHA256', $selector, true);

    $hash_encoded = base64_encode($hash_data);

    $cipher_data = password_hash($hash_encoded, PASSWORD_DEFAULT);

    return ScriptlogCryptonize::cipherMessage($cipher_data, $key);

  }

/**
 * getRandomSelectorProtected
 *
 * @param string $cipher_data
 * @param string $selector
 * @param string $key
 * @return void
 * 
 */
  public static function getRandomSelectorProtected($cipher_data, $selector, $key)
  {

    $decipher = ScriptlogCryptonize::decipherMessage($cipher_data, $key);

    $hash_data = hash('SHA256', $selector, true);

    $hash_encoded = base64_encode($hash_data);

    return password_verify($hash_encoded, $decipher);

  }

}