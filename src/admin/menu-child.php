<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$subMenuId = isset($_GET['subMenuId']) ? abs((int)$_GET['subMenuId']) : 0;
$menuChildDao = new MenuChildDao();
$validator = new FormValidator();
$menuChildEvent = new MenuChildEvent($menuChildDao, $validator, $sanitizer);
$menuChildApp = new MenuChildApp($menuChildEvent);
    
    switch ($action) {
    
        case ActionConst::NEWSUBMENU:
            # Add New Menu
            if ($subMenuId == 0) {
    
                $menuChildApp -> insert();
    
            }
    
            break;
        
        case ActionConst::EDITSUBMENU:
    
            if ($menuChildDao -> checkMenuChildId($subMenuId, $sanitizer)) {
    
                $menuChildApp -> update($subMenuId);
    
            } else {
    
                direct_page('index.php?load=menu&error=menuNotFound', 404);
    
            }
    
            break;
    
        case ActionConst::DELETESUBMENU:
    
            $menuChildApp -> remove($menuId);
    
        default:
            
            $menuChildApp -> listItems();
    
            break;
            
    }