<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$langId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;

try {

    switch ($action) {

        case ActionConst::NEWLANGUAGE:

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                $languageController = new LanguageController();
                $languageController->create();
            }

            break;

        case ActionConst::EDITLANGUAGE:

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                if ((!check_integer($langId)) && (gettype($langId) != "integer")) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type");
                }

                if ($langId > 0) {
                    $languageController = new LanguageController();
                    $languageController->edit((int)$langId);
                } else {
                    direct_page('index.php?load=languages', 302);
                }
            }

            break;

        case ActionConst::DELETELANGUAGE:

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                if ((!check_integer($langId)) && (gettype($langId) !== "integer")) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type");
                }

                if ($langId > 0) {
                    $languageController = new LanguageController();
                    $languageController->delete((int)$langId);
                } else {
                    direct_page('index.php?load=languages', 302);
                }
            }

            break;

        case 'setDefault':

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                if ((!check_integer($langId)) && (gettype($langId) !== "integer")) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type");
                }

                if ($langId > 0) {
                    $languageController = new LanguageController();
                    $languageController->setDefault((int)$langId);
                } else {
                    direct_page('index.php?load=languages', 302);
                }
            }

            break;

        default:

            if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $languageController = new LanguageController();
                $languageController->index();
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
