<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$themeId = isset($_GET['Id']) ? abs((int)$_GET['Id']) : 0;
$themeDao = new ThemeDao();
$themeEvent = new ThemeEvent($themeDao, $validator, $sanitizer);
$themeApp = new ThemeApp($themeEvent);

try {

    switch ($action) {

        case ActionConst::NEWTHEME:
            
            if (false === $authenticator->userAccessControl(ActionConst::THEMES)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($themeId)) && (gettype($themeId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("invalid ID data type!");
    
                }
    
                if ($themeId == 0) {
    
                    $themeApp->insert();
    
                } else {
    
                    direct_page('index.php?load=dashboard', 302);
                    
                }
    
            }
            
            break;
    
        case ActionConst::INSTALLTHEME:
            
            if (false === $authenticator->userAccessControl(ActionConst::THEMES)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($themeId)) && (gettype($themeId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("invalid ID data type!");
    
                } else {
    
                    if ($themeId == 0) {
    
                        $themeApp->setupTheme();
    
                    }
    
                }
    
            }
    
            break;
    
        case ActionConst::EDITTHEME:
    
            if (false === $authenticator->userAccessControl(ActionConst::THEMES)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ((!check_integer($themeId)) && (gettype($themeId) !== "integer")) {
    
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                    header("Status: 400 Bad Request");
                    throw new AppException("Invalid ID data type!");
    
                }
    
                if ($themeDao->checkThemeId($themeId, $sanitizer)) {
    
                    $themeApp->update((int)$themeId);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
    
                }
    
            }
        
            break;
    
        case ActionConst::DELETETHEME:
    
            if ((!check_integer($themeId)) && (gettype($themeId) !== "integer")) {
    
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                header("Status: 400 Bad Request");
                throw new AppException("Invalid ID data type!");
    
            }
    
            if (false === $authenticator->userAccessControl(ActionConst::THEMES)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
                
                if ($themeDao->checkThemeId($themeId, $sanitizer)) {
    
                    $themeApp->remove((int)$themeId);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);

                }
                
            }
    
           break;
    
        case ActionConst::ACTIVATETHEME:
    
            if ((!check_integer($themeId)) && (gettype($themeId) !== "integer")) {
    
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                header("Status: 400 Bad Request");
                throw new AppException("Invalid ID data type!");
    
            }
    
            if (false === $authenticator->userAccessControl(ActionConst::THEMES)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                if ($themeDao->checkThemeId($themeId, $sanitizer)) {
    
                    $themeApp->enableTheme((int)$themeId);
    
                } else {
    
                    direct_page('index.php?load=404&notfound='.notfound_id(), 404);
    
                }
    
            }
    
          break;
    
        default:
    
            if (false === $authenticator->userAccessControl(ActionConst::THEMES)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
                $themeApp -> listItems(); // show list of all themes
    
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