<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$subMenuId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$menuChildDao = new MenuChildDao();
$menuChildEvent = new MenuChildEvent($menuChildDao, $validator, $sanitizer);
$menuChildApp = new MenuChildApp($menuChildEvent);
    
switch ($action) {
    
    case ActionConst::NEWSUBMENU:
            # Add New Menu
        if (false === $authenticator->userAccessControl(ActionConst::NAVIGATION)) {

            direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

        } else {

            if ((!check_integer($subMenuId)) && (gettype($subMenuId, "integer"))) {

                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                throw new AppException("Invalid ID data type!");

            }

            if ($subMenuId == 0) {
    
                $menuChildApp -> insert();
        
            } else {

                direct_page('index.php?load=dashboard', 302);

            }

        }
    
        break;
        
    case ActionConst::EDITSUBMENU:
    
        if ((!check_integer($subMenuId)) && (gettype($subMenuId) !== "integer")) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Invalid ID data type!");

        }

        if (false === $authenticator->userAccessControl(ActionConst::NAVIGATION)) {

            direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

        } else {

            if ($menuChildDao -> checkMenuChildId($subMenuId, $sanitizer)) {
    
                $menuChildApp -> update((int)$subMenuId);
        
            } else {
        
                direct_page('index.php?load=menu-child&error=submenuNotFound', 404);
        
            }

        }
        
        break;
    
    case ActionConst::DELETESUBMENU:
    
        if ((!check_integer($subMenuId)) && (gettype($subMenuId) !== "integer")) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Invalid ID data type!");

        } 

        if (false === $authenticator->userAccessControl(ActionConst::NAVIGATION)) {

            direct_page('index.php?load=403&forbideen='.forbidden_id(), 403);

        } else {

            if ($menuChildDao->checkMenuChildId($subMenuId, $sanitizer)) {

                $menuChildApp -> remove((int)$subMenuId);
                
            } else {

                direct_page('index.php?load=menu-child&error=submenuNotFound', 404);

            }
            
        }
        
        break;

    default:
            
        if (false === $authenticator->userAccessControl(ActionConst::NAVIGATION)) {

            direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

        } else {

            $menuChildApp -> listItems();

        }
            
        break;
            
}