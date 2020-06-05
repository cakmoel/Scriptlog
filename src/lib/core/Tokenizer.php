<?php
/**
 * Tokenizer Class
 * Utitlize Cookies for authorization and authentication
 *
 * @category  Core Class
 * @author    Scott 
 * @see      https://stackoverflow.com/questions/1846202/php-how-to-generate-a-random-unique-alphanumeric-string
 * @see      https://phppot.com/php/secure-remember-me-for-login-using-php-session-and-cookies/
 * 
 */
class  Tokenizer
{

/**
 * Create token
 * 
 * @method public createToken()
 * @param integer|number $length
 * @return string
 * 
 */
  public function createToken($length)
  {
     
    $generator = (new \RandomLib\Factory())->getMediumStrengthGenerator();
    
    $token = $generator->generateString($length);

    return $token;

  }

  private function randomSecureToken($min, $max)
  {

    $range = $max - $min;

    if ($range < 1) {
            
      return $min; 
        
    }

    $log = ceil(log($range, 2));
    $bytes = (int) ($log / 8) + 1; 
    $bits = (int) $log + 1; 
    $filter = (int) (1 << $bits) - 1; 

    do {

      if(function_exists("random_bytes")) {

        $rnd = hexdec(bin2hex(random_bytes($bytes)));
 
      } elseif(function_exists("openssl_random_pseudo_bytes")) {
 
       $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
        
      } else {
 
        $rnd = hexdec(bin2hex(ircmaxell_random_generator($bytes)));
 
      }

      $rnd = $rnd & $filter; 

    } while ($rnd > $range);

      return $min + $rnd;

  }

}