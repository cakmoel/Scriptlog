<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");
 
$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$menuId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$menuDao = new MenuDao();
$menuEvent = new MenuEvent($menuDao, $validator, $sanitizer);
$menuApp = new MenuApp($menuEvent);

try {
    
    switch ($action) {
    
        case ActionConst::NEWMENU:
            # Add New Menu
            if (false === $authenticator->userAccessControl(ActionConst::NAVIGATION)) {

                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

            } else {

                if ((!check_integer($menuId)) && (gettype($menuId) !== "integer")) {

                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                    throw new AppException("invalid ID data type!");

                }
        
                if ($menuId == 0) {
    
                    $menuApp->insert();
        
                } else {

                    direct_page('index.php?load=dashboard', 302);
                    
                }

            }
        
            break;
        
        case ActionConst::EDITMENU:
    
            if ((!check_integer($menuId)) && (gettype($menuId) !== "integer")) {

                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                throw new AppException("Invalid ID data type!");
    
            }

            if (false === $authenticator->userAccessControl(ActionConst::NAVIGATION)) {

                direct_page('index.php?load=403&error=forbidden='.forbidden_id(), 403);

            } else {

                if ($menuDao->checkMenuId($menuId, $sanitizer)) {
    
                    $menuApp->update((int)$menuId);
        
                } else {
        
                    direct_page('index.php?load=menu&error=menuNotFound', 404);
        
                }

            }
            
            break;
    
        case ActionConst::DELETEMENU:
    
            if ((!check_integer($menuId)) && (gettype($menuId) !== "integer")) {

                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                throw new AppException("Invalid ID data type!");
    
            }

            if (false === $authenticator->userAccessControl(ActionConst::NAVIGATION)) {

                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

            } else {

                if ($menuDao->checkMenuId($menuId, $sanitizer)) {

                    $menuApp->remove((int)$menuId);
    
                } else {
    
                    direct_page('index.php?load=menu&error=menuNotFound', 404);
    
                }

            }
            
            break;
    
        default:
            
            if (false === $authenticator->userAccessControl(ActionConst::NAVIGATION)) {

                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

            } else {

                $menuApp->listItems();
                
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
    
