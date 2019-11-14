<?php 
/**
 * Topic class extends Dao
 *
 * @package   SCRIPTLOG/LIB/DAO/Topic
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class Topic extends Dao
{
  
  /**
   * overrides Dao constructor
   */
  public function __construct()
  {
	
	parent::__construct();
	
  }

  /**
   * Find All Topics
   * 
   * @param integer $position
   * @param integer $limit
   * @param string $orderBy
   * @return boolean|array|object
   */
  public function findTopics($orderBy = 'ID')
  {
    $sql = "SELECT ID, 
            topic_title, 
            topic_slug, 
            topic_status
            FROM tbl_topics 
            ORDER BY :orderBy DESC";

    $this->setSQL($sql);
    $topics = $this->findAll([':orderBy' => $orderBy]);
  
    if (empty($topics)) return false;
      
    return $topics;
      
  }
  
  /**
   * Find Topic by ID
   * 
   * @param integer $topicId
   * @param object $sanitize
   * @param static $fetchMode
   * @return boolean|array|object
   */
  public function findTopicById($topicId, $sanitize, $fetchMode = null)
  {
    $cleanId = $this->filteringId($sanitize, $topicId, 'sql');
    
    $sql = "SELECT ID, topic_title, topic_slug, topic_status
		    FROM tbl_topics WHERE ID = ?";
    
    $this->setSQL($sql);
    
    if (is_null($fetchMode)) {
        
        $topicDetails = $this->findRow([$cleanId]);
        
    } else {
        
        $topicDetails = $this->findRow([$cleanId], $fetchMode);
        
    }
    
    if (empty($topicDetails)) return false;
    
    return $topicDetails;
    
  }
  
  /**
   * Find Topic by Slug
   * 
   * @param string $slug
   * @param object $sanitize
   * @param static $fetchMode
   * @return boolean|array|object
   */
  public function findTopicBySlug($slug, $sanitize, $fetchMode = null)
  {
      $sql = "SELECT ID, topic_title
              FROM tbl_topics 
              WHERE topic_slug = ? AND topic_status = 'Y'";
      
      $slug_sanitized = $this->filteringId($sanitize, $slug, 'xss');
      
      $this->setSQL($sql);
      
      if (is_null($fetchMode)) {

          $topicDetails = $this->findRow([$slug_sanitized]);

      } else {

          $topicDetails = $this->findRow([$slug_sanitized], $fetchMode);
          
      }
      
      if (empty($topicDetails)) return false;
      
      return $topicDetails;
      
  }
  
  /**
   * Insert a new records
   * 
   * @method createCategory
   * @param string $title
   * @param string $slug
   */
  public function createTopic($bind)
  {
    
    $stmt = $this->create("tbl_topics", [
        'topic_title' => $bind['topic_title'], 
        'topic_slug' => $bind['topic_slug']
    ]);
    
    return $this->lastId();
    
  }

  /**
   * Update an existing records
   * 
   * @param string $title
   * @param string $slug
   * @param string $status
   * @param integer $ID
   */
  public function updateTopic($sanitize, $bind, $ID)
  {
      
   $id_sanitized = $this->filteringId($sanitize, $ID, 'sql'); 
   $stmt = $this->modify("tbl_topics", [
       'topic_title' => $bind['topic_title'],
       'topic_slug' => $bind['topic_slug'],
       'topic_status' => $bind['topic_status']
   ], "ID = {$id_sanitized}");
   
  }

  /**
   * Delete an existing records
   * 
   * @param integer $ID
   * @param string $sanitizing
   */
 public function deleteTopic($ID, $sanitize)
 {  	
  $cleanId = $this->filteringId($sanitize, $ID, 'sql');
  
  $stmt = $this->deleteRecord("tbl_topics", "ID = $cleanId");
 }

 /**
  * get post topic table
  * 
  * @param integer $topicId
  * @param integer $postId
  * @return boolean|array|object
  */
 public function getPostTopic($topicId, $postId)
 {
     $sql = "SELECT topic_id 
             FROM tbl_post_topic
             WHERE topic_id = :topic_id 
             AND post_id = :post_id";
     
     $this->setSQL($sql);
     
     $post_topic = $this->findRow(['topic_id' => $topicId, 'post_id' => $postId]);
     
     if (empty($post_topic)) return false;
     
     return $post_topic;
     
 }
 
 /**
  * Set topic
  * post category
  * 
  * @param string $postId
  * @param array $checked
  * @return string
  */
 public function setCheckBoxTopic($postId = '', $checked = null)
 {
                  
   if (is_null($checked)) {
       $checked = "checked='checked'";
    }
      
  $html = array();
  $html[] = '<div class="form-group">';
  $html[] = '<label>Category : </label>';

  $items = null;
  $items = $this->findTopics();
 
  if (empty($postId)) {
       
     if (is_array($items)) {
         
         foreach ($items as $i => $item) {
             
             if (isset($_POST['catID'])) {
                 
                 if (in_array($item['ID'], $_POST['catID'])) {
                     
                     $checked = "checked='checked'";
                     
                 } else {
                     
                     $checked = null;
                     
                 }
                 
             }
            
            $html[] = '<div class="checkbox">';
            $html[] = '<label>';
            $html[] = '<input type="checkbox" name="catID[]" value="'.$item['ID'].'"'.$checked.'>'.$item['topic_title'];
            $html[] = '</label>';
            $html[] = '</div>';
             
         }
         
     } else {
         
         $html[] = '<div class="checkbox">';
         $html[] = '<label>';
         $html[] = '<input type="checkbox" name="catID" value="0" checked>Uncategorized';
         $html[] = '</label>';
         $html[] = '</div>';
         
     }
    
    
 } else {
     
     if (is_array($items)) {

        foreach ($items as $item) {
         
            $post_topic = $this->getPostTopic($item['ID'], $postId);
               
            if ($post_topic['topic_id'] == $item['ID']) {
              
              $checked="checked='checked'";
            
            } else {
             
              $checked = null;
            }
               
               $html[] = '<div class="checkbox">';
               $html[] = '<label>';
               $html[] = '<input type="checkbox" name="catID[]" value="'.$item['ID'].'"'.$checked.'>'.$item['topic_title'];
               $html[] = '</label>';
               $html[] = '</div>';
               
           }

     }
     
 }
 
  $html[] = '</div>';
 
  return implode("\n", $html);
 
 }
 
/**
 * Check Id'topic
 * 
 * @method public checkTopicId()
 * @param integer $id
 * @param object $sanitize
 * @return numeric
 * 
 */
 public function checkTopicId($id, $sanitizing)
 {
   $sql = "SELECT ID FROM tbl_topics WHERE ID = ?";
   $cleanId = $this->filteringId($sanitizing, $id, 'sql');
   $this->setSQL($sql);
   $stmt = $this->checkCountValue([$cleanId]);
   return($stmt > 0);
 }

 /**
  * Total topic records
  * 
  * @param array $data
  * @return numeric
  *
  */
 public function totalTopicRecords($data = null)
 {
    $sql = "SELECT ID FROM tbl_topics";
    $this->setSQL($sql);
    return $this->checkCountValue($data);
 }

}