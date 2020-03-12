<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";  
$commentId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$commentDao = new CommentDao();
$commentEvent = new CommentEvent($commentDao, $validator, $sanitizer);
$commentApp = new CommentApp($commentEvent);
    
    switch ($action) {
        
        case ActionConst::EDITCOMMENT:
            
            if (false === $authenticator->userAccessControl(Action)) {

                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

            } else {

                if ($commentDao -> checkCommentId($commentId, $sanitizer)) {
                
                    $commentApp -> update(settype($commentId, "integer"));
                    
                } else {
                    
                    direct_page('index.php?load=comments&error=commentNotFound', 404);
                    
                }
                
            }
        
            break;
     
        case ActionConst::DELETECOMMENT:
            
            if (false === $authenticator->userAccessControl(ActionConst::COMMENTS)) {

                direct_page('index.php?load=403&');
                
            } else {

                if ((!check_integer($commentId)) && (gettype($commentId) !== "integer")) {

                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                    throw new AppException("Invalid ID data type!");

                }

                if ($commentDao->checkCommentId($commentId, $sanitizer)) {

                    $commentApp -> remove(settype($commentId, "integer"));

                } else {

                    direct_page('index.php?load=comments&error=commentNotFound', 404);

                }

            }
            
            break;
            
        default:
            
            if (false === $authenticator->userAccessControl(ActionConst::COMMENTS)) {

                direct_page('index.php?load=403&forbidden='.forbidden_id(), 403);

            } else {

                $commentApp -> listItems();

            }
            
        break;
        
    }