<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$postId = isset($_GET['postId']) ? abs((int)$_GET['postId']) : 0;
$postDao = new PostDao();
$validator = new FormValidator();
$postEvent = new PostEvent($postDao, $validator, $sanitizer);
$postApp = new PostApp($postEvent);

switch ($action) {
    
    case ActionConst::NEWPOST:
        
        if ($postId == 0) {
            
            $postApp -> insert();
            
        }
        
        break;
        
    case ActionConst::EDITPOST:
        
        if ($postDao -> checkPostId($postId, $sanitizer)) {
        
            $postApp -> update($postId);
            
        } else {
            
           direct_page('index.php?load=posts&error=postNotFound', 404);
            
        }
        
        break;
        
    case ActionConst::DELETEPOST:
        
        $postApp -> remove($postId);
        
        break;
        
    default:
        
        $postApp -> listItems();
        
        break;
        
}
