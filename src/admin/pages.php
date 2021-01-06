<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$pageId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$pageDao = new PageDao();
$pageEvent = new PageEvent($pageDao, $validator, $sanitizer);
$pageApp = new PageApp($pageEvent);

try {

    switch ($action) {
    
        case ActionConst::NEWPAGE:
        
            if (false === $authenticator->userAccessControl(ActionConst::PAGES)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($pageId)) && (gettype($pageId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                    throw new AppException("Invalid ID data type!");
       
                }
    
                if ($pageId == 0) {
                
                    $pageApp->insert();
                    
                } else {
    
                    direct_page('index.php?load=dashboard', 302);
    
                }
                
            }
            
            break;
        
        case ActionConst::EDITPAGE:
            
            if (false === $authenticator->userAccessControl(ActionConst::PAGES)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($pageId)) && (gettype($pageId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                    throw new AppException("Invalid ID data type!");
       
                }
    
                if ($pageDao->checkPageId($pageId, $sanitizer)) {
                
                    $pageApp->update((int)$pageId);
                    
                } else {
                    
                    direct_page('index.php?load=pages&error=pageNotFound', 404);
        
                }
                
            }
            
            break;
            
        case ActionConst::DELETEPAGE:
            
            if (false === $authenticator->userAccessControl(ActionConst::PAGES)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($pageId)) && (gettype($pageId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                    throw new AppException("Invalid ID data type");
    
                }
    
                if ($pageDao->checkPageId($pageId, $sanitizer)) {
    
                    $pageApp->remove((int)$pageId);
    
                } else {
    
                    direct_page('index.php?load=pages&error=pageNotFound', 404);
    
                }
                
            }
            
            break;
        
        default:
             
            if (false === $authenticator->userAccessControl(ActionConst::PAGES)) {
                
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                $pageApp->listItems();
    
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