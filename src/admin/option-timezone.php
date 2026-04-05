<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$params = isset($_GET['Id']) ? intval($_GET['Id']) : null ;
$configDao = class_exists('ConfigurationDao') ? new ConfigurationDao() : "";
$configService = class_exists('ConfigurationService') ? new ConfigurationService($configDao, $app->validator, $app->sanitizer) : "";
$configController = class_exists('ConfigurationController') ? new ConfigurationController($configService) : "";

try {
    switch ($action) {
        case ActionConst::TIMEZONE_CONFIG:
            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                if ((!check_integer($params)) && (gettype($params) !== "integer")) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type");
                }

                if ($params == 0) {
                    $configController->updateTimezoneConfig();
                } else {
                    direct_page('index.php?load=dashboard', 302);
                }
            }

            break;

        default:
            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $configController->updateTimezoneConfig();
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
