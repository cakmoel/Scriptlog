<?php

/**
 * turn_on_session()
 *
 * Checking too old session ID and start session
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param object $session_handler
 * @param int|numeric $life_time
 * @param string $cookies_name
 * @param string $path
 * @param string $domain
 * @param bool $secure
 * @param bool $httponly
 * @see https://www.php.net/manual/en/function.session-create-id.php
 * @return bool
 */
function turn_on_session($session_handler, $life_time, $cookies_name, $path, $domain, $secure, $httponly)
{
    if (!is_a($session_handler, 'SessionMaker')) {
        error_log("Invalid session handler provided to turn_on_session");
        return false;
    }

    try {
        // Start session with timeout protection
        set_time_limit(30); // Prevent infinite hangs

        $session_handler->start();

        // Check if session is valid (not expired, genuine IP/user-agent)
        if (!$session_handler->isValid()) {
            $session_handler->forget();
            $session_handler->start(); // Start fresh session
        }

        // Do not allow to use too old session ID
        if (!empty($_SESSION['deleted_time']) && $_SESSION['deleted_time'] < time() - $life_time) {
            $session_handler->forget();
            $session_handler->start();
            set_cookies_scl($cookies_name, session_id(), $life_time, $path, $domain, $secure, $httponly);
            $session_handler->refresh();
        }

        return true;
    } catch (ScriptlogCryptonizeException $e) {
        // Session decryption failed
        error_log("Session decryption error in turn_on_session: " . $e->getMessage());

        // Attempt to recover by destroying and starting fresh
        try {
            $session_handler->forget();
            $session_handler->start();
            return true;
        } catch (Exception $retryError) {
            error_log("Failed to recover session: " . $retryError->getMessage());
            return false;
        }
    } catch (Exception $e) {
        error_log("Unexpected error in turn_on_session: " . $e->getMessage());
        return false;
    }
}
