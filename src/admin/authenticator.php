<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * File authenticator.php - Refactored for AppContext
 */

$timeout = class_exists('Authentication') ? Authentication::COOKIE_EXPIRE : 2592000;
$current_date = date("Y-m-d H:i:s", time());
$uagent = $_SERVER['HTTP_USER_AGENT'] ?? "";

// $ip is defined in login.php before this file is required
$ip_for_hash = $ip ?? '';

$fingerprint  = hash_hmac('sha256', $uagent, hash('sha256', $ip_for_hash, true));
$loggedIn = false;

if (class_exists('Session')) {
    // Session Timeout and Fingerprint Check (Anti-Hijacking)
    if (
        (isset(Session::getInstance()->scriptlog_last_active) && Session::getInstance()->scriptlog_last_active < time() - $timeout)
        || (isset(Session::getInstance()->scriptlog_fingerprint)  && Session::getInstance()->scriptlog_fingerprint !== $fingerprint)
    ) {
        // Access authenticator via $app object
        do_logout(is_a($app->authenticator, 'Authentication') ? $app->authenticator : "");
    }

    // Path 1: Session Active
    if (!empty(Session::getInstance()->scriptlog_session_id)) {
        $loggedIn = true;

        // Path 2: Persistent Login (Remember Me) Check
    } elseif ((!empty($_COOKIE['scriptlog_auth'])) && (!empty($_COOKIE['scriptlog_validator'])) && (!empty($_COOKIE['scriptlog_selector']))) {
        $validator_verified = false;
        $selector_verified  = false;
        $expired_verified   = false;

        // Use $app->cipher_key and $app->authenticator
        $decrypt_auth = class_exists('ScriptlogCryptonize') ? ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $app->cipher_key) : "";

        // Ensure $app->authenticator is available
        $token_info = (isset($app->authenticator)) ? $app->authenticator->findTokenByLogin($decrypt_auth, 0) : [];

        // Check 1: Token Found
        if (!empty($token_info['ID'])) {
            if (hash_equals($token_info['pwd_hash'], Tokenizer::setRandomPasswordProtected($_COOKIE['scriptlog_validator']))) {
                $validator_verified = true;
            }

            $secret = class_exists('ScriptlogCryptonize') ? ScriptlogCryptonize::generateSecretKey() : "";
            if (hash_equals($token_info['selector_hash'], Tokenizer::setRandomSelectorProtected($_COOKIE['scriptlog_selector'], $secret))) {
                $selector_verified = true;
            }

            if ($token_info['expired_date'] >= $current_date) {
                $expired_verified = true;
            }
        }

        // --- Core Authentication Decision ---
        if (!empty($token_info['ID']) && $validator_verified && $selector_verified && $expired_verified) {
            $loggedIn = true;

            // 2. TOKEN RENEWAL
            Session::getInstance()->scriptlog_session_login = $token_info['user_login'];

            $encrypt_auth = ScriptlogCryptonize::scriptlogCipher($decrypt_auth, $app->cipher_key);
            set_cookies_scl('scriptlog_auth', $encrypt_auth, time() + $timeout, Authentication::COOKIE_PATH, domain_name(), is_cookies_secured(), true);

            $random_password = Tokenizer::createToken(128);
            set_cookies_scl('scriptlog_validator', $random_password, time() + $timeout, Authentication::COOKIE_PATH, domain_name(), is_cookies_secured(), true);

            $random_selector = Tokenizer::createToken(128);
            set_cookies_scl('scriptlog_selector', $random_selector, time() + $timeout, Authentication::COOKIE_PATH, domain_name(), is_cookies_secured(), true);

            $hashed_password = Tokenizer::setRandomPasswordProtected($random_password);
            $hashed_selector = Tokenizer::setRandomSelectorProtected($random_selector, $secret);

            $expiry_date = date("Y-m-d H:i:s", time() + $timeout);

            // Update DB via $app->authenticator
            $app->authenticator->markCookieAsExpired($token_info['ID']);

            $bind_token = ['user_login' => $decrypt_auth, 'pwd_hash' => $hashed_password, 'selector_hash' => $hashed_selector, 'is_expired' => 0, 'expired_date' => $expiry_date];

            $app->authenticator->renewPersistentLogin($bind_token, $token_info['user_login']);
        } else {
            // 3. FAILURE/INVALIDATION
            if (!empty($token_info['ID']) && isset($app->authenticator)) {
                $app->authenticator->markCookieAsExpired($token_info['ID']);
            }

            if (!empty($decrypt_auth) && isset($app->authenticator)) {
                $app->authenticator->clearAuthCookies($decrypt_auth);
            }
        }
    }
}
