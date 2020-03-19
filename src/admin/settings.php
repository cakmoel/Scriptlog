<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = (isset($_GET['action'])) ? htmlentities(strip_tags($_GET['action'])) : "";
$args = (isset($_GET['args'])) ? escape_html($_GET['args']) : null;
$configDao = new ConfigurationDao();
$configEvent = new ConfigurationEvent($configDao, $validator, $sanitizer);
$configApp = new ConfigurationApp($configEvent);

switch ($action) {

    case ActionConst::EDITCONFIG:
        
        if (false === $authenticator -> userAccessControl(ActionConst::CONFIGURATION)) {

            direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

        } else {

            # edit configuration
            $configApp -> update($args);

        }

        break;
    
    default: 
    
       if (false === $authenticator -> userAccessControl(ActionConst::CONFIGURATION)) {

          direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

       } else {

          $configApp -> listItems();
          
       }
       
       break;

}

