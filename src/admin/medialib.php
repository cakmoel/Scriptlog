<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$mediaId = isset($_GET['mediaId']) ? abs((int)$_GET['mediaId']) : 0;
$mediaDao = new Media();
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

       if ($mediaDao -> checkMediaId($mediaId, $sanitizer)) {

           $mediaLib -> update($mediaId);

       } else {

           direct_page('index.php?load=medialib&error=mediaNotFound', 404);
            
       } 

       break;

    case ActionConst::DELETEMEDIA:

         $mediaLib -> remove($mediaId);
         
       break;

    default:

        $mediaLib -> listItems();

        break;

}