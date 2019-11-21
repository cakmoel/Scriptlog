<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$settingId = isset($_GET['settingId']) ? abs((int)$_GET['settingId']) : 0;
$configDao = new ConfigurationDao();
$validator = new FormValidator();
$configEvent = new ConfigurationEvent($configDao, $validator, $sanitizer);
$configApp = new ConfigurationApp($configEvent);

switch ($action) {

    case ActionConst::NEWCONFIG:

        if($settingId == 0) {

           $configApp -> insert();

        }

        break;

    case ActionConst::EDITCONFIG:
        
        # edit configuration
        if ($configDao -> checkConfigId($settingId, $sanitizer)) {

            $configApp -> update($settingId);
            
        } else {

            direct_page('index.php?load=settings&error=configNotFound', 404);
            
        }

        break;

    case ActionConst::DELETECONFIG:
      
        // delete setting
        
        break;
    
    default:

       # display setting
       $configApp -> listItems();

       break;

}

