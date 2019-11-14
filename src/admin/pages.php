<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$pageId = isset($_GET['pageId']) ? abs((int)$_GET['pageId']) : 0;
$pageDao = new Page();
$validator = new FormValidator();
$pageEvent = new PageEvent($pageDao, $validator, $sanitizer);
$pageApp = new PageApp($pageEvent);

switch ($action) {
    
    case ActionConst::NEWPAGE:
    
        if ($pageId == 0) {
            
            $pageApp -> insert();
            
        }
        
        break;
    
    case ActionConst::EDITPAGE:
        
        if ($pageDao -> checkPageId($pageId, $sanitizer)) {
            
            $pageApp -> update($pageId);
            
        } else {
            
            
        }

        break;
        
    case ActionConst::DELETEPAGE:
        
        $pageApp -> remove($pageId);
        
        break;
    
    default:
        
        $pageApp -> listItems();
        
    break;
    
}