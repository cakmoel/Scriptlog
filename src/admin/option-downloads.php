<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * option-downloads.php
 *
 * Download settings admin page
 *
 * @category Admin Page
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 */

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";

$configDao = class_exists('ConfigurationDao') ? new ConfigurationDao() : "";
$configService = class_exists('ConfigurationService') ? new ConfigurationService($configDao, $app->validator, $app->sanitizer) : "";
$configController = class_exists('ConfigurationController') ? new ConfigurationController($configService) : "";

try {
    switch ($action) {
        case ActionConst::DOWNLOAD_CONFIG:
            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $configController->updateDownloadSetting();
            }

            break;

        default:
            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $configController->updateDownloadSetting();
            }

            break;
    }
} catch (Throwable $th) {
    if (class_exists('LogError')) {
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);
    }
} catch (AppException $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
}
