<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";  
$commentId = isset($_GET['commentId']) ? abs((int)$_GET['commentId']) : 0;
$commentDao = new CommentDao();
$validator = new FormValidator();
$commentEvent = new CommentEvent($commentDao, $validator, $sanitizer);
$commentApp = new CommentApp($commentEvent);
    
    switch ($action) {
        
        case ActionConst::EDITCOMMENT:
        
            if ($commentDao -> checkCommentId($commentId, $sanitizer)) {
                
                $commentApp -> update($commentId);
                
            } else {
                
                direct_page('index.php?load=comments&error=commentNotFound', 404);
                
            }
            
            break;
     
        case ActionConst::DELETECOMMENT:
            
            $commentApp -> remove($commentId);
            
            break;
            
        default:
            
            $commentApp -> listItems();
            
        break;
        
    }