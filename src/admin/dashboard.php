<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$displayWall = new Wall();

switch ($action) {
    
    case 'detailItem':
      # code ...

       break;
       
    default:
        
        $displayWall -> listItems();
        
        break;
       
}
