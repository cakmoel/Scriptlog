<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$pluginId = isset($_GET['pluginId']) ? abs((int)$_GET['pluginId']) : 0;
$pluginDao = new PluginDao();
$validator = new FormValidator();
$pluginEvent = new PluginEvent($pluginDao, $validator, $sanitizer);
$pluginApp = new PluginApp($pluginEvent);

switch ($action) {

    case ActionConst::INSTALLPLUGIN:
        
        if ($pluginId == 0) {
            
            $pluginApp -> installPlugin();
            
        } else {

            direct_page('index.php?load=dashboard', 200);

        }

        break;
    
    case ActionConst::ACTIVATEPLUGIN:
        
        $pluginApp -> enablePlugin($pluginId);

        break;

    case ActionConst::DEACTIVATEPLUGIN:
        
        $pluginApp -> disablePlugin($pluginId);

        break;

    case ActionConst::NEWPLUGIN:
       
       if ($pluginId == 0) {

          $pluginApp -> insert();

       } else {

          direct_page('index.php?load=dashboard', 200);
          
       }

       break;

    case ActionConst::EDITPLUGIN:
       
       if ($pluginDao -> checkPluginId($pluginId, $sanitizer)) {

          $pluginApp -> update($pluginId);

       } else {
         
           direct_page('index.php?load=plugins&error=pluginNotFound', 404);

       }

       break;

    case ActionConst::DELETEPLUGIN:
       
        $pluginApp -> remove($pluginId);

       break;

    default:
        
        $pluginApp -> listItems();
        
        break;

}
