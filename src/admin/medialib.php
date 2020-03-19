<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$mediaId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$mediaDao = new MediaDao();
$mediaEvent = new MediaEvent($mediaDao, $validator, $sanitizer);
$mediaLib = new MediaApp($mediaEvent);

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

       if ((!check_integer($mediaId)) && (gettype($mediaId) !== "integer")) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Invalid ID data type!");

       }
       
       if (false === $authenticator->userAccessControl(ActionConst::MEDIALIB)) {

           direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

       } else {

         if ($mediaDao->checkMediaId($mediaId, $sanitizer)) {

             $mediaLib->update(settype($mediaId, "integer"));
 
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
   
              $mediaLib->remove(settype($mediaId, "integer"));
   
          } else {
   
            direct_page('index.php?load=medialib&error=mediaNotFound', 404);
   
          }

      }
         
       break;

    default:

        if( false === $authenticator->userAccessControl(ActionConst::MEDIALIB)) {

            direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

        } else {

           $mediaLib -> listItems();

        }
        
        break;

}