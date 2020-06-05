<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$commentId = isset($_GET['commentId']) ? abs((int)$_GET['commentId'])  : 0;
$replyId = isset($_GET['replyId']) ? abs((int)$_GET['replyId']) : 0;
$replyDao = new ReplyDao();


switch ($action) {

    case 'replyComment':
        
        
        break;
    
    default:
        
        
        break;
}
