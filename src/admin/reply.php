<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$replyId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$replyDao = new ReplyDao();
$replyEvent = new ReplyEvent($replyDao, $validator, $sanitizer);
$replyApp = new ReplyApp($replyEvent);

try {

    switch ($action) {

        default:
        case ActionConst::NEWREPLY:
            
            if ( false === $authenticator->userAccessControl(ActionConst::REPLY)) {

                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

            } else {

                if((!check_integer($replyId)) && (gettype($replyId) != "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                    throw new AppException("Invalid ID data type");
    
                }

                if ($replyId === 0) {

                    $replyApp->insert();

                } else {

                    direct_page('index.php?load=dashboard', 302);

                }

            }

            break;
        
    }
    
} catch (Throwable $th) {

    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($th);

} catch (AppException $e) {

    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);

}