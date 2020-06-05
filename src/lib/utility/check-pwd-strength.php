<?php 
/**
 * check password strength function
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @since 1.0
 * @param string $password
 * 
 * Path to research, learn and code:
 * 
 * @see https://www.the-art-of-web.com/javascript/validate-password/
 * @see https://www.the-art-of-web.com/php/password-strength/
 * @see https://www.imtiazepu.com/password-validation/
 * @see https://cafewebmaster.com/check-password-strength-safety-php-and-regex
 * @see https://www.codexworld.com/how-to/validate-password-strength-in-php/
 * @see https://stackoverflow.com/questions/10752862/password-strength-check-in-php
 * @see https://phppot.com/jquery/jquery-password-strength-checker/
 * @see https://stackoverflow.com/questions/8141125/regex-for-password-php
 * @see https://stackoverflow.com/questions/48345922/reference-password-validation
 * @see https://security.stackexchange.com/questions/18197/why-shouldnt-we-roll-our-own
 * @see https://www.zorched.net/2009/05/08/password-strength-validation-with-regular-expressions/
 * @see https://stackoverflow.com/questions/2637896/php-regular-expression-for-strong-password-validation
 * @see https://techearl.com/regular-expressions/regex-password-strength-validation
 * 
 * @return bool
 * 
 */
function check_pwd_strength($password, $strong_type = 'standard')
{

  $strength = true;

    switch ($strong_type) {
        
        case 'strict':
            
            if (!preg_match('/^(?=(?:.*[A-Z]){2,})(?=(?:.*[a-z]){2,})(?=(?:.*\d){2,})(?=(?:.*[!@#$%^&*()\-_=+{};:,<.>]){2,})(.{8,})$/', $password)) {

                $strength = false;

            }

            break;
        
        default:
            
            if (!preg_match('/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[\W])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $password)) {

              $strength = false;
    
            }    
            
            break;

    }

    return $strength;

}