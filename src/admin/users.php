<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$userId = isset($_GET['Id']) ? abs((int)$_GET['Id']) : 0;
$sessionId = isset($_GET['sessionId']) ? $_GET['sessionId'] : null;
$userEvent = new UserEvent($userDao, $validator, $sanitizer);
$userApp = new UserApp($userEvent);

switch ($action) {
    
    case ActionConst::NEWUSER:
    
      if (false === $authenticator -> userAccessControl(ActionConst::NEWUSER)) {

          direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

      } else {

        if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {

           header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
           throw new AppException("invalid ID data type!");

        } 

        if ($userId == 0) {

            $userApp -> insert();

        } else {

            direct_page('index.php?load=dashboard', 302);
            
        }

      }
        
      break;
        
    case ActionConst::EDITUSER:
        
        if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Invalid ID data type!");

        }

        if ($userDao -> checkUserId($userId, $sanitizer)) {

            if (($authenticator -> accessLevel() != 'administrator') && ($authenticator -> accessLevel() != 'manager')) {

                $userApp -> updateProfile($user_login);

            } else {

                $userApp -> update($userId);

            }

        } else {

            if ($authenticator -> accessLevel() != 'administrator') {

                 direct_page('index.php?load=404&notfound='.notfound_id(), 404);

            } else {

                 direct_page('index.php?load=users&error=userNotFound', 404);

            }
             
        }

        if (false === $userDao -> checkUserSession($sessionId)) {

            if ($authenticator -> accessLevel() != 'administrator') {

                direct_page('index.php?load=404&notfound='.notfound_id(), 404);
                
            } else {

                direct_page('index.php?load=users&error=userNotFound', 404);

            }

        }

        break;
        
    case ActionConst::DELETEUSER:
 
        if(false === $authenticator -> userAccessControl(ActionConst::DELETEUSER)) {

            direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

        } else {

            if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {

                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                throw new AppException("Invalid ID data type!");
    
            }
            
            if ($userDao -> checkUserId($userId, $sanitizer)) {

                $userApp -> remove($userId);
            
            } else {

                direct_page('index.php?load=users&error=userNotFound', 404);

            }
            
        }
        
        break;
                
    default:
        
       if (($authenticator -> accessLevel() !== 'administrator') && ($authenticator -> accessLevel() !== 'manager')) {

           $userApp -> showProfile($user_login);

       } else {

          $userApp -> listItems();

      }
    
      break;
        
}