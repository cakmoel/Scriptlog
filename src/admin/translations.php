<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$translationId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;

try {

    switch ($action) {

        case ActionConst::NEWTRANSLATION:

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                $translationController = new TranslationController();
                $translationController->create();
            }

            break;

        case ActionConst::DELETETRANSLATION:

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                if ((!check_integer($translationId)) && (gettype($translationId) !== "integer")) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type");
                }

                if ($translationId > 0) {
                    $translationController = new TranslationController();
                    $translationController->delete((int)$translationId);
                } else {
                    direct_page('index.php?load=translations', 302);
                }
            }

            break;

        case 'export':

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                $translationController = new TranslationController();
                $translationController->export();
            }

            break;

        case 'import':

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                $translationController = new TranslationController();
                $translationController->import();
            }

            break;

        case 'regenerateCache':

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                $translationController = new TranslationController();
                $translationController->regenerateCache();
            }

            break;

        case 'update':

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
            } else {

                $translationController = new TranslationController();
                $translationController->update();
            }

            break;

        default:

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $translationController = new TranslationController();
                $translationController->index();
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
