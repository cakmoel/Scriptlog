<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$userId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$sessionId = isset($_GET['sessionId']) ? safe_html($_GET['sessionId']): null;
$userService = class_exists('UserService') ? new UserService($app->userDao, $app->validator, $app->userToken, $app->sanitizer) : "";
$userController = class_exists('UserController') ? new UserController($userService) : "";

try {

    switch ($action) {
    
        case ActionConst::NEWUSER:
        
          if (false === $app->authenticator->userAccessControl(ActionConst::USERS)) {
    
            direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
          } else {
    
            if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {
    
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                header("Status: 400 Bad Request");
                throw new AppException("invalid ID data type!");
    
            } 
    
            if ($userId === 0) {
    
                $userController->insert();
    
            } else {
    
                direct_page('index.php?load=dashboard', 302);
                
            }
    
          }
            
          break;
            
        case ActionConst::EDITUSER:
            
            if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {
    
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                header("Status: 400 Bad Request");
                throw new AppException("Invalid ID data type!");
    
            }

            if (!$app->userDao->checkUserId($userId, $app->sanitizer)) {
    
                if (false === $app->authenticator->userAccessControl(ActionConst::USERS)) {
    
                    direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
    
                }
    
            } else {
    
                if (false === $app->authenticator->userAccessControl(ActionConst::USERS)) {
    
                    $userController->updateProfile($user_login);
        
                } else {
        
                    $userController->update((int)$userId);
        
                }
    
            }
    
            break;
            
        case ActionConst::DELETEUSER:
     
            if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {
    
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                header("Status: 400 Bad Request");
                throw new AppException("Invalid ID data type!");
    
            }

            if (!$app->userDao->checkUserId($userId, $app->sanitizer)) {

                if (false === $app->authenticator->userAccessControl(ActionConst::USERS)) {
    
                    direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
    
                }

            } else {

                if (false === $app->authenticator->userAccessControl(ActionConst::USERS)) {

                    $userController->removeProfile($user_login, $app->authenticator);

                } else {

                    $userController->remove((int)$userId);

                }

            }
            
            break;
                    
        default:
            
           if (false === $app->authenticator->userAccessControl(ActionConst::USERS)) {
    
             $userController->showProfile($user_login);
    
           } else {
    
             $userController->listItems();
    
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