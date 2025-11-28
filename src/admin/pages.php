<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$pageId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$pageDao = class_exists('PageDao') ? new PageDao() : "";
$pageService = class_exists('PageService') ? new PageService($pageDao, $validator, $sanitizer) : "";
$pageController = class_exists('PageController') ? new PageController($pageService) : "";

try {

    switch ($action) {
    
        case ActionConst::NEWPAGE:
        
            if (false === $authenticator->userAccessControl(ActionConst::PAGES)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($pageId)) && (gettype($pageId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type!");
       
                }
    
                if ($pageId == 0) {
                
                    $pageController->insert();
                    
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
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type!");
       
                }
    
                if ($pageDao->checkPageId($pageId, $sanitizer)) {
                
                    $pageController->update((int)$pageId);
                    
                } else {
                    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
        
                }
                
            }
            
            break;
            
        case ActionConst::DELETEPAGE:
            
            if (false === $authenticator->userAccessControl(ActionConst::PAGES)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($pageId)) && (gettype($pageId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type");
    
                }
    
                if ($pageDao->checkPageId($pageId, $sanitizer)) {
    
                    $pageController->remove((int)$pageId);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
    
                }
                
            }
            
            break;
        
        default:
             
            if (false === $authenticator->userAccessControl(ActionConst::PAGES)) {
                
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                $pageController->listItems();
    
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