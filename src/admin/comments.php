<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$commentId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$commentDao = class_exists('CommentDao') ? new CommentDao() : "";
$commentService = class_exists('CommentService') ? new CommentService($commentDao, $app->validator, $app->sanitizer) : "";
$commentController = class_exists('CommentController') ? new CommentController($commentService) : "";

try {
    switch ($action) {
        case ActionConst::EDITCOMMENT:
            if (false === $app->authenticator->userAccessControl(ActionConst::COMMENTS)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                if ($commentDao->checkCommentId($commentId, $app->sanitizer)) {
                    $commentController->update((int)$commentId);
                } else {
                    direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
                }
            }

            break;

        case ActionConst::DELETECOMMENT:
            if (false === $app->authenticator->userAccessControl(ActionConst::COMMENTS)) {
                direct_page('index.php?load=403&');
            } else {
                if ((!check_integer($commentId)) && (gettype($commentId) !== "integer")) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type!");
                }

                if ($commentDao->checkCommentId($commentId, $app->sanitizer)) {
                    $commentController->remove((int)$commentId);
                } else {
                    direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
                }
            }

            break;

        default:
            if (false === $app->authenticator->userAccessControl(ActionConst::COMMENTS)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $commentController->listItems();
            }
    }
} catch (Throwable $th) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($th);
} catch (AppException $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
}
