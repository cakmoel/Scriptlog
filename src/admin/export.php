<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";

if (empty($action)) {
    if (isset($_POST['exportSubmit'])) {
        $action = 'export';
    }
}

$exportService = new ExportService();
$exportController = new ExportController($exportService);

try {
    switch ($action) {
        case 'export':
            if (false === $app->authenticator->userAccessControl(ActionConst::PRIVACY)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $exportController->export();
            }

            break;

        default:
            if (false === $app->authenticator->userAccessControl(ActionConst::PRIVACY)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $exportController->index();
            }

            break;
    }
} catch (\Throwable $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
} catch (AppException $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
}
