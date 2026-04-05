<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";

if (empty($action)) {
    if (isset($_POST['previewSubmit'])) {
        $action = 'preview';
    } elseif (isset($_POST['importSubmit'])) {
        $action = 'import';
    }
}

$migrationService = new MigrationService($app->sanitizer);
$importController = new ImportController($migrationService);

try {
    switch ($action) {
        case 'preview':
            if (false === $app->authenticator->userAccessControl(ActionConst::IMPORT)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $importController->preview();
            }

            break;

        case 'import':
            if (false === $app->authenticator->userAccessControl(ActionConst::IMPORT)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $importController->import();
            }

            break;

        default:
            if (false === $app->authenticator->userAccessControl(ActionConst::IMPORT)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $importController->index();
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
