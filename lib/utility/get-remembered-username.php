<?php

/**
 * Safely retrieve username from remember-me cookie
 * FIX: Prevents fatal errors from corrupted cookies or key mismatches
 */
function get_remembered_username(): string
{
    if (empty($_COOKIE['scriptlog_auth']) || !class_exists('ScriptlogCryptonize') || !isset($GLOBALS['app']->cipher_key)) {
        return '';
    }

    try {
        $decrypted = \ScriptlogCryptonize::scriptlogDecipher(
            $_COOKIE['scriptlog_auth'],
            $GLOBALS['app']->cipher_key
        );
        return is_string($decrypted) ? $decrypted : '';
    } catch (\Throwable $e) {
        // Corrupted cookie or key mismatch — silently ignore
        return '';
    }
}
