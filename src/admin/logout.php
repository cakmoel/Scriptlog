<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? safe_html($_GET['action']) : "";
$logOutId =  isset($_GET['logOutId']) ? safe_html($_GET['logOutId']) : null;

try {
    switch ($action) {
        case ActionConst::LOGOUT:
            if (false === $app->authenticator->userAccessControl()) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                // Check if user is authenticated via Remember Me cookie
                $hasRememberMe = isset($_COOKIE['scriptlog_auth']) && !empty($_COOKIE['scriptlog_auth']);
                
                // Validate logout token
                $valid_logout = !empty($logOutId) && verify_logout_id($logOutId);
                
                // If token is invalid but user has Remember Me cookie, allow logout
                // This is the fix for the "URL Redirection to Untrusted Site" error
                if (!$valid_logout && $hasRememberMe) {
                    // Proceed with logout for Remember Me users
                    $app->authenticator->logout();
                } elseif ($valid_logout) {
                    // Normal logout with valid token
                    $app->authenticator->logout();
                } else {
                    // Neither valid token nor Remember Me - possible security issue
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("URL Redirection to Untrusted Site");
                }
            }
            break;

        default:
            if (false === $app->authenticator->userAccessControl()) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                direct_page('index.php?load=dashboard', 302);
            }
    }
} catch (\Throwable $th) {
    if (class_exists('LogError')) {
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);
    }
} catch (AppException $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
}