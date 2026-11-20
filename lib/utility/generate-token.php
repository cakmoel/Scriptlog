<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * generate_token()
 *
 * this will generate a random token string
 *
 * @category Function
 * @param number $length
 * @return string
 *
 */
function generate_token($length = 20)
{

    // random_bytes() is a cryptographically secure random number generator
    // (CSPRNG) available on all PHP versions supported by Scriptlog (>= 7.4).
    // The OpenSSL fallback is kept only as a defensive path and is itself
    // CSPRNG-backed - token material must never fall back to an insecure
    // source such as RandomLib MEDIUM or mt_rand().
    if (function_exists("random_bytes")) {
        return bin2hex(random_bytes($length));
    }

    return bin2hex(openssl_random_pseudo_bytes($length));
}
