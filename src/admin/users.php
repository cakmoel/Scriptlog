<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$userId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$sessionId = isset($_GET['sessionId']) ? safe_html($_GET['sessionId']): null;
$userEvent = new UserEvent($userDao, $validator, $userToken, $sanitizer);
$userApp = new UserApp($userEvent);

try {

    switch ($action) {
    
        case ActionConst::NEWUSER:
        
          if (false === $authenticator->userAccessControl(ActionConst::USERS)) {
    
              direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
          } else {
    
            if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {
    
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                header("Status: 400 Bad Request");
                throw new AppException("invalid ID data type!");
    
            } 
    
            if ($userId == 0) {
    
                $userApp->insert();
    
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

            if ((!$userDao->checkUserId($userId, $sanitizer))) {
    
                if (false === $authenticator->userAccessControl(ActionConst::USERS)) {
    
                    direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
               } else {
    
                    direct_page('index.php?load=users&error=userNotFound', 404);
    
               }
    
            } else {
    
                if (false === $authenticator->userAccessControl(ActionConst::USERS)) {
    
                    $userApp->updateProfile($user_login);
        
                } else {
        
                    $userApp->update((int)$userId);
        
                }
    
            }
    
            break;
            
        case ActionConst::DELETEUSER:
     
            if(false === $authenticator->userAccessControl(ActionConst::USERS)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type!");
        
                }
                
                if ($userDao->checkUserId($userId, $sanitizer)) {
    
                    $userApp->remove((int)$userId);
                
                } else {
    
                    direct_page('index.php?load=users&error=userNotFound', 404);
    
                }
                
            }
            
            break;
                    
        default:
            
           if (false === $authenticator->userAccessControl(ActionConst::USERS)) {
    
               $userApp->showProfile($user_login);
    
           } else {
    
              $userApp->listItems();
    
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