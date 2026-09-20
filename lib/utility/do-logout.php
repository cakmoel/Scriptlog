<?php

/**
 * do_logout()
 *
 * logging out session and cookies
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param object $authenticator
 * @return void
 */
function do_logout($authenticator)
{

    if ($authenticator instanceof \Scriptlog\Core\Authentication) {
        return $authenticator->logout();
    }
}

/**
 * do_logout_id()
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 *
 */
function do_logout_id()
{

    $prefix = isset(Session::getInstance()->scriptlog_fingerprint)
        ? Session::getInstance()->scriptlog_fingerprint
        : hash_hmac('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '', hash('sha256', app_key(), true));

    $id_logout = uniqid($prefix, true);

    // Initialize loggingOut array if it doesn't exist
    if (!isset($_SESSION['loggingOut']) || !is_array($_SESSION['loggingOut'])) {
        $_SESSION['loggingOut'] = [];
    }

    $_SESSION['loggingOut'][$id_logout] = true;

    return $id_logout;
}

/**
 * verify_logout_id()
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $id_logout
 * @return boolean
 *
 */
function verify_logout_id($id_logout)
{
    // Check if loggingOut array exists and the ID is valid
    if (isset($_SESSION['loggingOut']) && is_array($_SESSION['loggingOut']) && isset($_SESSION['loggingOut'][$id_logout])) {
        unset($_SESSION['loggingOut'][$id_logout]);
        return true;
    }

    return false;
}
