<?php
/**
 * Tokenizer Class
 * Utitlize Cookies for authorization and authentication
 *
 * @package   SCRIPTLOG/LIB/CORE/Tokenizer
 * @category  Core Class
 * @author    Vincy vincy@gmail.com
 * @link      https://phppot.com/php/secure-remember-me-for-login-using-php-session-and-cookies/
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
     $token = "";
     $key  = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
     $key .= "!@#$%^&*?{}";
     $key .= "0123456789";
     $key .= "abcdefghijklmnopqrstuvwxyz";
     
     $max = strlen($key) - 1;
        
       for ($i = 0; $i < $length; $i ++) {

            $token .= $key[$this->randomSecureToken(0, $max)];

        }
        
      return $token;

  }

  public function randomSecureToken($min, $max)
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

            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; 

        } while ($rnd >= $range);

        return $min + $rnd;

  }

  
}