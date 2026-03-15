<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * File authenticator.php
 * 
 * *checking whether session cookies exists
 * 
 * @category authenticator.php checking whether cookies or session exists or not
 * @author M.Noermoehammad scriptlog@yandex.com
 * @author Vincy vincy@gmail.com
 * @license MIT
 * @version 1.0
 * @link https://phppot.com/php/secure-remember-me-for-login-using-php-session-and-cookies/
 * @link https://stackoverflow.com/questions/1846202/php-how-to-generate-a-random-unique-alphanumeric-string
 * @link https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
 * @link https://en.wikibooks.org/wiki/PHP_Programming/Sessions#Avoiding_Session_Fixation
 * @link https://stackoverflow.com/a/17266448/6667699
 * */
$timeout = class_exists('Authentication') ? Authentication::COOKIE_EXPIRE : 2592000;
$current_date = date("Y-m-d H:i:s", time());
$uagent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";

$ip_for_hash = $ip ?? ''; 

$fingerprint  = hash_hmac('sha256', $uagent, hash('sha256', $ip_for_hash, true));
$loggedIn = false;

if (class_exists('Session')) {

    // Session Timeout and Fingerprint Check (Anti-Hijacking)
    if ((isset(Session::getInstance()->scriptlog_last_active) && Session::getInstance()->scriptlog_last_active < time() - $timeout)
        || (isset(Session::getInstance()->scriptlog_fingerprint)  && Session::getInstance()->scriptlog_fingerprint !== $fingerprint)
    ) {

        do_logout(is_a($authenticator, 'Authentication') ? $authenticator : "");
    }

    // Path 1: Session Active
    if (!empty(Session::getInstance()->scriptlog_session_id)) {

        $loggedIn = true;

    // Path 2: Persistent Login (Remember Me) Check
    } elseif ((!empty($_COOKIE['scriptlog_auth'])) && (!empty($_COOKIE['scriptlog_validator'])) && (!empty($_COOKIE['scriptlog_selector']))) {

        $validator_verified = false;
        $selector_verified  = false;
        $expired_verified   = false;

        $decrypt_auth = class_exists('ScriptlogCryptonize') ? ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $cipher_key) : "";
        $token_info = $authenticator->findTokenByLogin($decrypt_auth, 0);

        // Check 1: Token Found (proceed only if a matching token record was found)
        if (!empty($token_info['ID'])) {

            // Check 2: Validator Hash Comparison (SECURE FIX: Using hash_equals for constant-time comparison)
            if (hash_equals($token_info['pwd_hash'], Tokenizer::setRandomPasswordProtected($_COOKIE['scriptlog_validator']))) {

                $validator_verified = true;
            }

            // Check 3: Selector Hash Comparison (SECURE FIX: Using hash_equals)
            $secret = class_exists('ScriptlogCryptonize') ? ScriptlogCryptonize::generateSecretKey() : "";
            if (hash_equals($token_info['selector_hash'], Tokenizer::setRandomSelectorProtected($_COOKIE['scriptlog_selector'], $secret))) {

                $selector_verified = true;
            }

            // Check 4: Expiry Date
            if ($token_info['expired_date'] >= $current_date) {

                $expired_verified = true;
            }
        } 

        // --- Core Authentication Decision ---
        // Success Path (Token Found AND All checks passed)
        if (!empty($token_info['ID']) && $validator_verified && $selector_verified && $expired_verified) {

            $loggedIn = true;

            // 2. TOKEN RENEWAL (Same original logic)
            Session::getInstance()->scriptlog_session_login = $token_info['user_login'];

            $encrypt_auth = ScriptlogCryptonize::scriptlogCipher($decrypt_auth, $cipher_key);
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

            is_a($authenticator, 'Authentication') ? $authenticator->renewPersistentLogin($bind_token, $token_info['user_login']) : "";

        // Failure Path (Token not found, validation failed, or expired)
        } else { 
            
            // 3. FAILURE/INVALIDATION (Corrected flow logic)

            // Only mark as expired if we found a record to begin with (prevents unnecessary DB calls)
            if (!empty($token_info['ID'])) {
                is_a($authenticator, 'Authentication') ? $authenticator->markCookieAsExpired($token_info['ID']) : "";
            }
            
            // Clear the browser cookies using the user login value (must be present to clear cookies)
            if (!empty($decrypt_auth)) {
                is_a($authenticator, 'Authentication') ? $authenticator->clearAuthCookies($decrypt_auth) : "";
            }
        }
       
    }
}