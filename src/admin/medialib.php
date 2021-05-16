<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$mediaId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$mediaDao = new MediaDao();
$mediaEvent = new MediaEvent($mediaDao, $validator, $sanitizer);
$mediaLib = new MediaApp($mediaEvent);

try {

    switch ($action) {

        case ActionConst::NEWMEDIA: // new media
    
           if (false === $authenticator->userAccessControl(ActionConst::MEDIALIB)) {
    
               direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
           } else {
    
               if ((!check_integer($mediaId)) && (gettype($mediaId) !== "integer")) {
    
                   header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                   throw new AppException("Invalid ID data type!");
    
               }
               
               if ($mediaId == 0) {
    
                    $mediaLib->insert();
      
                } else {
      
                     direct_page('index.php?load=dashboard', 302);
                
                }
              
           }
        
           break;
    
        case ActionConst::EDITMEDIA:
    
           if (false === $authenticator->userAccessControl(ActionConst::MEDIALIB)) {
    
               direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
           } else {
    
             if ((!check_integer($mediaId)) && (gettype($mediaId) !== "integer")) {
    
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                throw new AppException("Invalid ID data type!");
    
             }
    
             if ($mediaDao->checkMediaId($mediaId, $sanitizer)) {
    
                 $mediaLib->update((int)$mediaId);
     
             } else {
     
                 direct_page('index.php?load=medialib&error=mediaNotFound', 404);
                 
             }
              
           }
        
           break;
    
        case ActionConst::DELETEMEDIA:
    
          if (false === $authenticator->userAccessControl(ActionConst::MEDIALIB)) {
    
              direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
          } else {
    
             if ((!check_integer($mediaId)) && (gettype($mediaId) !== "integer")) {
    
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                throw new AppException("Invalid ID data type!");
       
              }
              
              if ($mediaDao->checkMediaId($mediaId, $sanitizer)) {
       
                  $mediaLib->remove((int)$mediaId);
       
              } else {
       
                direct_page('index.php?load=medialib&error=mediaNotFound', 404);
       
              }
    
          }
             
           break;
    
        default:
    
            if( false === $authenticator->userAccessControl(ActionConst::MEDIALIB)) {
    
                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
    
            } else {
    
               $mediaLib->listItems();
    
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