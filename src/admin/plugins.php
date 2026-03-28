<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$pluginId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$pluginDao = class_exists('PluginDao') ? new PluginDao() : "";
$pluginService = class_exists('PluginService') ? new PluginService($pluginDao, $app->validator, $app->sanitizer) : "";
$pluginController = class_exists('PluginController') ? new PluginController($pluginService) : "";

try {
    
    switch ($action) {

        case ActionConst::INSTALLPLUGIN:
            
            if (false === $app->authenticator->userAccessControl(ActionConst::PLUGINS)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($pluginId)) && (gettype($pluginId))) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type!");
    
                }

                if ($pluginId == 0) {
    
                    $pluginController->installPlugin();
    
                } else {
    
                    direct_page('index.php?load=dashboard', 302);
                    
                }
    
            }
    
            break;
        
        case ActionConst::ACTIVATEPLUGIN:
    
            if (false === $app->authenticator->userAccessControl(ActionConst::PLUGINS)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ($pluginDao->checkPluginId($pluginId, $app->sanitizer)) {
                   
                    $pluginController->enablePlugin((int)$pluginId);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
    
                }
    
            }
           
            break;
    
        case ActionConst::DEACTIVATEPLUGIN:
            
            if (false === $app->authenticator->userAccessControl(ActionConst::PLUGINS)) {
    
                 direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ($pluginDao->checkPluginId($pluginId, $app->sanitizer)) {
    
                    $pluginController->disablePlugin((int)$pluginId);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
    
                }
                
            }
        
            break;
    
        case ActionConst::DELETEPLUGIN:
            
            if (false === $app->authenticator->userAccessControl(ActionConst::PLUGINS)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($pluginId)) && (gettype($pluginId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type!");
        
                }
    
                if ($pluginDao->checkPluginId($pluginId, $app->sanitizer)) {
    
                    $pluginController->remove((int)$pluginId);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
    
                }
            }
            
           break;
    
        default:
            
            if (false === $app->authenticator->userAccessControl(ActionConst::PLUGINS)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                $pluginController->listItems();
    
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
