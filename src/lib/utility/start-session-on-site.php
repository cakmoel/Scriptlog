<?php

/**
 * start_session_on_site
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @see https://www.php.net/manual/en/ref.session.php
 * @see https://github.com/GoogleChromeLabs/samesite-examples/blob/master/php.md
 * @see https://stackoverflow.com/questions/36877/how-do-you-set-up-use-httponly-cookies-in-php
 * @see https://stackoverflow.com/a/46971326/2308553
 * @uses turn_on_session() functions
 * @param object $session_handler
 * @return bool
 */
function start_session_on_site($session_handler)
{
    try {
        $life_time = class_exists('Authentication') ? Authentication::COOKIE_EXPIRE : 3600;
        $session_name = session_name();

        if (ini_get('session.use_cookies')) {
            $current_cookie_params = session_get_cookie_params();
        } else {
            // Fallback if cookies disabled
            $current_cookie_params = [
                'path' => '/',
                'domain' => '',
                'secure' => is_cookies_secured(),
                'httponly' => true
            ];
        }

        $_SESSION['deleted_time'] = time();

        if (isset($_COOKIE[$session_name])) {
            $session_id = $_COOKIE[$session_name];
        } elseif (isset($_GET[$session_name])) {
            $session_id = $_GET[$session_name];
        } else {
            return turn_on_session(
                $session_handler,
                $life_time,
                $session_name,
                $current_cookie_params["path"],
                $current_cookie_params["domain"],
                $current_cookie_params["secure"],
                true
            );
        }

        if (!session_valid_id($session_id)) {
            error_log("Invalid session ID detected: " . substr($session_id, 0, 10) . "...");
            return false;
        }

        return turn_on_session(
            $session_handler,
            $life_time,
            $session_name,
            $current_cookie_params["path"],
            $current_cookie_params["domain"],
            $current_cookie_params["secure"],
            true
        );
    } catch (Exception $e) {
        error_log("Error in start_session_on_site: " . $e->getMessage());
        return false;
    }
}

/**
 * session_valid_id
 *
 * @category function
 * @license MIT
 * @version 1.0
 * @see https://www.php.net/manual/en/function.session-id.php
 * @see https://akrabat.com/validating-default-php-session-id-values/
 * @param string $session_id
 * @return bool
 */
function session_valid_id($session_id)
{
    if (!is_string($session_id) || empty($session_id)) {
        return false;
    }

    try {
        if (PHP_VERSION_ID >= 70100) {
            $sidLength = ini_get('session.sid_length');

            switch (ini_get('session.sid_bits_per_character')) {
                case 6:
                    $characterClass = '0-9a-zA-z,-';
                    break;
                case 5:
                    $characterClass = '0-9a-v';
                    break;
                case 4:
                    $characterClass = '0-9a-f';
                    break;
                default:
                    error_log('Unknown value in session.sid_bits_per_character: ' . ini_get('session.sid_bits_per_character'));
                    return false;
            }

            $pattern = '/^[' . $characterClass . ']{' . $sidLength . '}$/';
            return preg_match($pattern, $session_id) === 1;
        } else {
            return preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $session_id) === 1;
        }
    } catch (Exception $e) {
        error_log("Error validating session ID: " . $e->getMessage());
        return false;
    }
}
