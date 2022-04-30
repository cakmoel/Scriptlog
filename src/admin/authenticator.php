<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * File authenticator.php
 * 
 * checking whether session or cookies exists
 * 
 * @category authenticator.php checking whether cookies or session exists or not
 * @author M.Noermoehammad scriptlog@yandex.com
 * @author Vincy vincy@gmail.com
 * @license MIT
 * @version 1.0
 * @see https://phppot.com/php/secure-remember-me-for-login-using-php-session-and-cookies/
 * @see https://stackoverflow.com/questions/1846202/php-how-to-generate-a-random-unique-alphanumeric-string
 * @see https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
 * @see https://en.wikibooks.org/wiki/PHP_Programming/Sessions#Avoiding_Session_Fixation
 * @see https://stackoverflow.com/a/17266448/6667699
 * 
 */
$timeout = Authentication::COOKIE_EXPIRE;
$current_date = date("Y-m-d H:i:s", time()); 
$fingerprint  = hash_hmac('sha256', $_SERVER['HTTP_USER_AGENT'], hash('sha256', $ip, true));
$loggedIn = false;

if ( ( isset(Session::getInstance()->scriptlog_last_active) && Session::getInstance()->scriptlog_last_active < time()-$timeout  ) 
    || ( isset(Session::getInstance()->scriptlog_fingerprint)  && Session::getInstance()->scriptlog_fingerprint != $fingerprint ) ) {
        
    do_logout($authenticator);
        
}

if (!empty(Session::getInstance()->scriptlog_session_id)) {

    $loggedIn = true;

} elseif ((!empty($_COOKIE['scriptlog_auth'])) && (!empty($_COOKIE['scriptlog_validator'])) && (!empty($_COOKIE['scriptlog_selector']))) {  

    $secret = ScriptlogCryptonize::generateSecretKey();

    $validator_verified = false;
    $selector_verified  = false;
    $expired_verified   = false;
 
    $decrypt_auth = ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $key);
    $token_info = $authenticator->findTokenByLogin($decrypt_auth, 0);

    $expected_validator = crypt($_COOKIE['scriptlog_validator'], $token_info['pwd_hash']);
    $correct_validator = crypt($_COOKIE['scriptlog_validator'], $token_info['pwd_hash']);
    $expected_selector = crypt($_COOKIE['scriptlog_selector'], $token_info['selector_hash']);
    $correct_selector = crypt($_COOKIE['scriptlog_selector'], $token_info['selector_hash']);

    if ( ! function_exists('hash_equals') ) {

        if((timing_safe_equals($expected_validator, $correct_validator) == 0) && (Tokenizer::getRandomPasswordProtected($_COOKIE['scriptlog_validator'], $token_info['pwd_hash'] ) ) ) {

            $validator_verified = true;

        }

        if ((timing_safe_equals($expected_selector, $correct_selector) == 0) && (Tokenizer::getRandomSelectorProtected($_COOKIE['scriptlog_selector'], $token_info['selector_hash'], $secret ) ) ) {

            $selector_verified = true;

        }

    } else {

        if( ( hash_equals($expected_validator, $correct_validator) ) && (Tokenizer::getRandomPasswordProtected($_COOKIE['scriptlog_validator'], $token_info['pwd_hash'] ) ) ) {

            $validator_verified = true;

        }

        if ( ( hash_equals($expected_selector, $correct_selector) ) && (Tokenizer::getRandomSelectorProtected($_COOKIE['scriptlog_selector'], $token_info['selector_hash'], $secret ) ) ) {

            $selector_verified = true;

        }

    }

    if ($token_info['expired_date'] >= $current_date) {

        $expired_verified = true;

    }

    if ((!empty($token_info['ID'])) && $validator_verified && $selector_verified && $expired_verified ) {

        $loggedIn = true;
    
        Session::getInstance()->scriptlog_session_login = $token_info['user_login'];

        $encrypt_auth = ScriptlogCryptonize::scriptlogCipher($decrypt_auth, $key);
        set_cookies_scl('scriptlog_auth', $encrypt_auth, time() + $timeout, Authentication::COOKIE_PATH, domain_name(), is_cookies_secured(), true); 
        
        $random_password = Tokenizer::createToken(128);
        set_cookies_scl('scriptlog_validator', $random_password, time() + $timeout, Authentication::COOKIE_PATH, domain_name(), is_cookies_secured(), true);

        $random_selector = Tokenizer::createToken(128);
        set_cookies_scl('scriptlog_selector', $random_selector, time() + $timeout, Authentication::COOKIE_PATH, domain_name(), is_cookies_secured(), true);

        $hashed_password = Tokenizer::setRandomPasswordProtected($random_password);
        $hashed_selector = Tokenizer::setRandomSelectorProtected($random_selector, $secret);

        $expiry_date = date("Y-m-d H:i:s", time() + $timeout);

        $authenticator->markCookieAsExpired($token_info['ID']);

        $bind_token = ['user_login' => $decrypt_auth, 'pwd_hash' => $hashed_password, 'selector_hash' => $hashed_selector, 'is_expired' => 0, 'expired_date' => $expiry_date];

        $authenticator->renewPersistentLogin($bind_token, $token_info['user_login']);

    } else {

        if (!empty($token_info['ID'])) {

            $authenticator->markCookieAsExpired($token_info['ID']);

        }

        $authenticator->clearAuthCookies($token_info['user_login']);
         
    } 

}