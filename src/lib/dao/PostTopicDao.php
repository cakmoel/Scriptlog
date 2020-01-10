<?php 
/**
 * PostTopic class extends Model
 * 
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

class PostTopicDao extends Dao
{
  
  public function __construct()
  {
    parent::__construct();
        
  }
  
  /**
   * Find post topic
   * 
   * @param integer $postId
   * @param object $sanitize
   * @return boolean|array|object
   */
  public function findPostTopic($postId, $sanitize)
  {
    $sql = "SELECT topic_title, topic_slug 
            FROM tbl_topics, tbl_post_topic
            WHERE tbl_topics.ID = tbl_post_topic.topic_id
            AND tbl_post_topic.post_id = :post_id";
    
    $cleanId = $this->filteringId($sanitize, $postId, 'sql');
    
    $this->setSQL($sql);
    
    $post_topics = $this->findRow([':post_id' => $cleanId]);
    
    return (empty($post_topics)) ?: $post_topics;
    
  }
  
  /**
   * Set Link Topics
   * 
   * @param integer $postId
   * @param object $sanitize
   * @param string $position
   * @return string
   */
  public function setLinkTopics($postId, $sanitize, $position = 'meta')
  {
    $url = APP_PROTOCOL.'://'.APP_HOSTNAME.dirname(dirname($_SERVER['PHP_SELF'])).'/';
    
    $html = array();
   
    $linkCategories = $this->findPostTopic($postId, $sanitize);
   
    foreach ($linkCategories as $l => $linkCategory) {
       
        if (!$position) {
        
            $html[] = '<a href="'.$url.'category/'.preventInject($linkCategory['category_slug']).'" class="tag-name">'.preventInject($linkCategory->topic_title).'</a>';
        
        } else {
            
            $html[] = prevent_injection($linkCategory -> topic_title);
            
        }
    }
   
    return implode(", ", $html);
  
  }
  
  /**
   * show post by topic
   * 
   * @param integer $topicId
   * @param object $sanitize
   * @return boolean|array|object
   */
  public function showPostByTopic($topicId, $sanitize)
  {
      $cleanId = $this->filteringId($sanitize, $topicId, 'sql');
      
      $sql = "SELECT
                tbl_posts.ID, tbl_posts.post_author, tbl_posts.post_date,
                tbl_posts.post_title, tbl_posts.post_slug, tbl_posts.post_content,
                tbl_posts.post_status, tbl_posts.post_type, tbl_users.user_login
           FROM
                tbl_posts, tbl_post_topic, tbl_users
           WHERE
                tbl_posts.ID = tbl_post_topic.post_id
           
           AND tbl_post_topic.topic_id = :topic_id
           AND tbl_posts.post_author = tbl_users.ID
           AND tbl_posts.post_status = 'publish' AND tbl_posts.post_type = 'blog'
           
           ORDER BY tbl_posts.post_id DESC ";
      
      $this -> setSQL($sql);
      
      $postByTopics = $this->findAll([':topic_id' => $cleanId]);
      
      return (empty($postByTopics)) ?: $postByTopics;
      
  }
              
}