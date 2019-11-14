<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$topicId = isset($_GET['topicId']) ? abs((int)$_GET['topicId']) : 0;
$topicDao = new Topic();
$validator = new FormValidator();
$topicEvent = new TopicEvent($topicDao, $validator, $sanitizer);
$topicApp = new TopicApp($topicEvent);

switch ($action) {
    
    case ActionConst::NEWTOPIC:
        
        if ($topicId == 0) {
            $topicApp -> insert();
        }
        
        break;
        
    case ActionConst::EDITTOPIC:
        
        if ($topicDao -> checkTopicId($topicId, $sanitizer)) {
            
            $topicApp -> update($topicId);
            
        } else {

            direct_page('index.php?load=topics&error=topicNotFound', 404);
            
        }
        
        break;
        
    case ActionConst::DELETETOPIC:
        
        $topicApp -> remove($topicId);
        
        break;
        
    default:
        
        $topicApp -> listItems();
        
        break;
        
}
