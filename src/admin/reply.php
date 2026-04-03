<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$replyId = isset($_GET['Id']) ? abs((int)$_GET['Id']) : 0;
$replyDao = new ReplyDao();
$replyService = new ReplyService($replyDao, $app->validator, $app->sanitizer);
$replyController = new ReplyController($replyService);

try {
    switch ($action) {
        case ActionConst::REPLY:
        case 'newReply':
            // New reply - Id is the PARENT comment ID, not a reply ID
            // Id=0 means we're creating a reply without a specific parent
            // Id>0 means we're replying to a specific comment

            if (false === $app->authenticator->userAccessControl(ActionConst::REPLY)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                // If POST, process form submission
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $replyController->insert();
                } else {
                    // GET request - show reply form
                    $replyController->insert();
                }
            }
            break;

        case ActionConst::EDITREPLY:
            // Edit existing reply - Id is the reply ID

            if (false === $app->authenticator->userAccessControl(ActionConst::REPLY)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                if ($replyId === 0) {
                    direct_page('index.php?load=comments', 302);
                }

                if ($replyService->checkReplyExists($replyId)) {
                    $replyController->update($replyId);
                } else {
                    direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
                }
            }
            break;

        case ActionConst::DELETEREPLY:
            // Delete reply - Id is the reply ID

            if (false === $app->authenticator->userAccessControl(ActionConst::REPLY)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                if ((!check_integer($replyId)) && (gettype($replyId) != "integer")) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type");
                }

                if ($replyService->checkReplyExists($replyId)) {
                    $replyController->remove($replyId);
                } else {
                    direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
                }
            }
            break;

        default:
            if (false === $app->authenticator->userAccessControl(ActionConst::REPLY)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                direct_page('index.php?load=comments', 302);
            }
    }
} catch (Throwable $th) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($th);
} catch (AppException $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
}
