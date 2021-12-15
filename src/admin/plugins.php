<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$pluginId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$pluginDao = new PluginDao();
$pluginEvent = new PluginEvent($pluginDao, $validator, $sanitizer);
$pluginApp = new PluginApp($pluginEvent);

try {
    
    switch ($action) {

        case ActionConst::INSTALLPLUGIN:
            
            if (false === $authenticator->userAccessControl(ActionConst::PLUGINS)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($pluginId)) && (gettype($pluginId))) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type!");
    
                }

                if ($pluginId == 0) {
    
                    $pluginApp->installPlugin();
    
                } else {
    
                    direct_page('index.php?load=dashboard', 302);
                    
                }
    
            }
    
            break;
        
        case ActionConst::ACTIVATEPLUGIN:
    
            if (false === $authenticator->userAccessControl(ActionConst::PLUGINS)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ($pluginDao->checkPluginId($pluginId, $sanitizer)) {
                   
                    $pluginApp->enablePlugin((int)$pluginId);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
    
                }
    
            }
           
            break;
    
        case ActionConst::DEACTIVATEPLUGIN:
            
            if (false === $authenticator->userAccessControl(ActionConst::PLUGINS)) {
    
                 direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ($pluginDao->checkPluginId($pluginId, $sanitizer)) {
    
                    $pluginApp->disablePlugin((int)$pluginId);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
    
                }
                
            }
        
            break;
    
        case ActionConst::DELETEPLUGIN:
            
            if (false === $authenticator->userAccessControl(ActionConst::PLUGINS)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($pluginId)) && (gettype($pluginId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type!");
        
                }
    
                if ($pluginDao->checkPluginId($pluginId, $sanitizer)) {
    
                    $pluginApp->remove((int)$pluginId);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
    
                }
            }
            
           break;
    
        default:
            
            if (false === $authenticator->userAccessControl(ActionConst::PLUGINS)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                $pluginApp->listItems();
    
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
