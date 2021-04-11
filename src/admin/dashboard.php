<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? safe_html($_GET['action']) : "";
$displayWall = new Wall();

try {
   
   switch ($action) {
    
      case 'detailItem':
  
         break;
         
      default:
          
         if (false === $authenticator->userAccessControl()) {
  
            direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);
  
         } else {
  
           $displayWall->listItems($authenticator, $user_login);
  
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
