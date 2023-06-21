<?php defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$displayWall = class_exists('Wall') ? new Wall() : "";

try {

   switch ($action) {
      
      case ActionConst::DETAILITEM:
         break;
         
      default:

         if (false === $authenticator->userAccessControl(ActionConst::DASHBOARD)) {

            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);

         } else {

            $displayWall->listItems($authenticator, $user_login);
         }

         break;
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
