<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$topicId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$topicDao = new TopicDao();
$topicEvent = new TopicEvent($topicDao, $validator, $sanitizer);
$topicApp = new TopicApp($topicEvent);

try {

    switch ($action) {
    
        case ActionConst::NEWTOPIC:
            
            if( false === $authenticator->userAccessControl(ActionConst::TOPICS)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if((!check_integer($topicId)) && (gettype($topicId) != "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                    throw new AppException("Invalid ID data type");
    
                }
    
                if ($topicId == 0) {
    
                    $topicApp->insert();
    
                } else {
    
                    direct_page('index.php?load=dashboard', 302);
    
                }
    
            }
            
            break;
            
        case ActionConst::EDITTOPIC:
            
            if( false === $authenticator->userAccessControl(ActionConst::TOPICS)) {
    
                 direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
    
                if ((!check_integer($topicId)) && (gettype($topicId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                    throw new AppException("Invalid ID data type");
    
                }
    
                if ($topicDao->checkTopicId($topicId, $sanitizer)) {
                
                    $topicApp->update((int)$topicId);
                    
                } else {
        
                    direct_page('index.php?load=topics&error=topicNotFound', 404);
                    
                }
                
            }
            
            break;
            
        case ActionConst::DELETETOPIC:
    
            if ((!check_integer($topicId)) && (gettype($topicId) !== "integer")) {
    
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                throw new AppException("Invalid ID data type");
    
            }
            
            if(false === $authenticator->userAccessControl(ActionConst::TOPICS)) {
    
                 direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                 if($topicDao->checkTopicId($topicId, $sanitizer)) {
    
                    $topicApp->remove((int)$topicId);
    
                 } else {
    
                     direct_page('index.php?load=topics&error=topicNotFound', 404);
    
                 }
                 
            }
            
            break;
            
        default:
            
            if(false === $authenticator->userAccessControl(ActionConst::TOPICS)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                $topicApp->listItems();
    
            }
        
            break;
            
    }

} catch (Throwable $th) {

    LogError::setStatusCode(http_response_code());
    LogError::newMessage($th);
    LogError::customErrorMessage('admin');

} catch (AppException $e) {

    LogError::setStatusCode(http_response_code());
    LogError::newMessage($e);
    LogError::customErrorMessage('admin');
    
}