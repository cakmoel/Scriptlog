<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$userId = isset($_GET['Id']) ? abs((int)$_GET['Id']) : "";
$sessionId = isset($_GET['sessionId']) ? $_GET['sessionId'] : "";
$userEvent = new UserEvent($userDao, $validator, $sanitizer);
$userApp = new UserApp($userEvent);

switch ($action) {
    
    case ActionConst::NEWUSER:
    
      if (false === $authenticator -> userAccessControl('users')) {

          direct_page('index.php?load=users&error=userNotFound', 404);

      } else {

        if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {

           header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
           throw new AppException("invalid ID data type!");

        } else {

            if ($userId == 0) {

                $userApp -> insert();

            }

        }

      }
        
      break;
        
    case ActionConst::EDITUSER:
        
        if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Invalid ID data type!");

        }

        if ($userDao -> checkUserId($userId, $sanitizer)) {
            
            if(false === $authenticator -> userAccessControl('users')) {
    
                $userApp -> updateProfile($user_id);
    
            } else {
    
                $userApp -> update($userId);
    
            }
            
        } elseif (false === $userDao -> checkUserSession($sessionId)) {
    
            direct_page('index.php?load=users&error=userNotFound', 404);
                
        } else {
            
            direct_page('index.php?load=users&error=userNotFound', 404);
                
        }

        break;
        
    case ActionConst::DELETEUSER:
        
        if(false === $authenticator -> userAccessControl('users')) {

            direct_page('index.php?load=users&error=userNotFound', 404);

        } else {

            $userApp -> remove($userId);

        }
        
        break;
                
    default:
        
        if(false === $authenticator -> userAccessControl('users')) {

            $userApp -> showProfile($user_id);

        } else {

            $userApp -> listItems();
            
        }
        
        break;
        
}
