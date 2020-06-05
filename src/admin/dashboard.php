<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? safe_html($_GET['action']) : "";
$displayWall = new Wall();

switch ($action) {
    
    case 'detailItem':
      # code ...

       break;
       
    default:
        
       if (false === $authenticator->userAccessControl()) {

          direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

       } else {

         $displayWall -> listItems();

       }
        
      break;
       
}
