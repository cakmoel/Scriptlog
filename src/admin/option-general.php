<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = (isset($_GET['action'])) ? htmlentities(strip_tags($_GET['action'])) : "";
$params = (isset($_GET['Id'])) ? intval($_GET['Id']) : null;
$configDao = new ConfigurationDao();
$configEvent = new ConfigurationEvent($configDao, $validator, $sanitizer);
$configApp = new ConfigurationApp($configEvent);

try {
    
    switch ($action) {

        case ActionConst::GENERAL_CONFIG:
        
            if(false === $authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if((!check_integer($params)) && (gettype($params) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");
                    throw new AppException("Invalid ID data type");
    
                }
    
                if($params == 0) {
    
                    $configApp->updateGeneralSetting();
    
                } else {
    
                    direct_page('index.php?load=dashboard', 302);
                    
                }
        
            }
        
          break;
    
        default:
    
           if(false === $authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
    
               direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
           } else {
    
              $configApp->updateGeneralSetting();
    
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