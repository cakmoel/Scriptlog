<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$mediaId = isset($_GET['Id']) ? abs((int)$_GET['Id']) : 0;
$mediaDao = new MediaDao();
$validator = new FormValidator();
$mediaEvent = new MediaEvent($mediaDao, $validator, $sanitizer);
$mediaLib = new MediaApp($mediaEvent);

switch ($action) {

    case ActionConst::NEWMEDIA:

       if ($mediaId == 0) {

          $mediaLib -> insert();

       } else {

          direct_page('index.php?load=dashboard', 200);
          
       }

       break;

    case ActionConst::EDITMEDIA:

       if ((!check_integer($mediaId)) && (gettype($mediaId) !== "integer")) {

         header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
         throw new AppException("Invalid ID data type!");

       }
       
       if ($mediaDao -> checkMediaId($mediaId, $sanitizer)) {

           $mediaLib -> update($mediaId);

       } else {

           direct_page('index.php?load=medialib&error=mediaNotFound', 404);
            
       } 

       break;

    case ActionConst::DELETEMEDIA:

      if ((!check_integer($mediaId)) && (gettype($mediaId) !== "integer")) {

         header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
         throw new AppException("Invalid ID data type!");

       }
       
       if ($mediaDao -> checkMediaId($mediaId, $sanitizer)) {

           $mediaLib -> remove($mediaId);

       } else {

         direct_page('index.php?load=medialib&error=mediaNotFound', 404);

       }
         
       break;

    default:

        $mediaLib -> listItems();

        break;

}