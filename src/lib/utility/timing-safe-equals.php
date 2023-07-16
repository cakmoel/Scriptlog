<?php
/**
 * timing_safe_equals
 * 
 * @category function
 * @author Anthony Ferrara
 * @link https://blog.ircmaxell.com/2014/11/its-all-about-time.html
 * @link https://blog.ircmaxell.com/2012/12/seven-ways-to-screw-up-bcrypt.html
 * @link https://stackoverflow.com/questions/1354999/keep-me-logged-in-the-best-approach/17266448#17266448
 * @param string $safe The internal (safe) value to be checked
 * @param string $user The user submitted (unsafe) value
 * @return boolean True if the two strings are identical.
 * 
 */
function timing_safe_equals($safe, $user)
{

  $safe .= chr(0);
  $user .= chr(0);
  
  if (function_exists('mb_strlen')) {
  
    $safeLen = mb_strlen($safe, '8bit');
    $userLen = mb_strlen($user, '8bit');
  
  } else {
  
    $safeLen = strlen($safe);
    $userLen = strlen($user);
  
  }
      
  $result = $safeLen - $userLen;
      
  for ($i = 0; $i < $userLen; $i++) {
  
    $result |= (ord($safe[$i % $safeLen]) ^ ord($user[$i])); 
          
  }
      
  // They are only identical strings if $result is exactly 0...
  return $result === 0;
   
}