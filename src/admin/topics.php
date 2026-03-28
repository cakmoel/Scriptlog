<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$topicId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$topicDao = class_exists('TopicDao') ? new TopicDao() : "";
$topicService = class_exists('TopicService') ? new TopicService($topicDao, $app->validator, $app->sanitizer) : "";
$topicController = class_exists('TopicController') ? new TopicController($topicService) : "";

try {

    switch ($action) {

        case ActionConst::NEWTOPIC:

            if (false === $app->authenticator->userAccessControl(ActionConst::TOPICS)) {

                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                if ((!check_integer($topicId)) && (gettype($topicId) != "integer")) {

                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type");
                }

                if ($topicId == 0) {

                    $topicController->insert();
                } else {

                    direct_page('index.php?load=dashboard', 302);
                }
            }

            break;

        case ActionConst::EDITTOPIC:

            if (false === $app->authenticator->userAccessControl(ActionConst::TOPICS)) {

                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                if ((!check_integer($topicId)) && (gettype($topicId) !== "integer")) {

                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type");
                }

                if ($topicDao->checkTopicId($topicId, $app->sanitizer)) {

                    $topicController->update((int)$topicId);
                } else {

                    direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
                }
            }

            break;

        case ActionConst::DELETETOPIC:

            if ((!check_integer($topicId)) && (gettype($topicId) !== "integer")) {

                header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                header("Status: 400 Bad Request");
                throw new AppException("Invalid ID data type");
            }

            if (false === $app->authenticator->userAccessControl(ActionConst::TOPICS)) {

                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                if ($topicDao->checkTopicId($topicId, $app->sanitizer)) {

                    $topicController->remove((int)$topicId);
                } else {

                    direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
                }
            }

            break;

        default:

            if (false === $app->authenticator->userAccessControl(ActionConst::TOPICS)) {

                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {

                $topicController->listItems();
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
