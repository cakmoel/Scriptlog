<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? safe_html($_GET['action']) : "";
$logOutId =  isset($_GET['logOutId']) ? safe_html($_GET['logOutId']) : null;

try {

    switch ($action) {

        case ActionConst::LOGOUT:

            if (false === $authenticator->userAccessControl()) {

                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);

            } else {

                $valid_logout = !empty($logOutId) && verify_logout_id($logOutId);

                if (!$valid_logout) {

                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("URL Redirection to Untrusted Site");
                    
                } else {

                    $authenticator->logout();
                }
            }

            break;

        default:

            if (false === $authenticator->userAccessControl()) {

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
