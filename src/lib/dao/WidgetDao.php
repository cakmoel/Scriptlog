<?php 
/**
 * Class Widget extends Dao
 * 
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class WidgetDao extends Dao
{
 public function __construct()
 {
   parent::__construct();
 }
 
 public function setNextNavigation($postId, $sanitize, $fetchMode = null)
 {
  $id_sanitized = $this->filteringId($sanitize, $postId, 'sql');
  
  $nextQuery = "SELECT ID, post_title, post_slug, post_type
                FROM tbl_posts WHERE ID > :ID 
                AND post_status = 'publish' AND post_type = 'blog' 
                ORDER BY ID LIMIT 1";
  
  $this->setSQL($sql);
   
  $nextLink = (is_null($fetchMode)) ? $this->findRow([':ID' => $id_sanitized]) : $this->findRow([':ID' => $id_sanitized], $fetchMode);
  
  return (empty($nextLink)) ?: $nextLink;
  
 }
 
 public function setPrevNavigation($postId, $sanitize, $fetchMode = null)
 {
     
   $id_sanitized = $this->filteringId($sanitize, $postId, 'sql');
     
   $prevQuery = "SELECT ID, post_title, post_slug, post_type 
                 FROM tbl_posts WHERE ID < :ID 
                 AND post_status = 'publish' AND post_type = 'blog' 
                 ORDER BY ID LIMIT 1";
     
   $this->setSQL($sql);
   
   if (is_null($fetchMode)) {
       
      $prevQuery = $this->findRow([':ID' => $id_sanitized]);
      
   } else {
       
      $prevQuery = $this->findRow([':ID' => $id_sanitized], $fetchMode);
      
   }
   
   if (empty($prevQuery)) return false;
   
   return $prevQuery;
     
 }
 
 public function setSidebarTopics()
 {
     
  $catQuery = "SELECT ID, topic_title, topic_slug, topic_status
              FROM tbl_topics WHERE topic_status = 'Y' 
              ORDER BY topic_title DESC";
   
  $this->setSQL($sql);
  
  $sidebarTopics = $this->findRow();
  
  if (empty($sidebarTopics)) return false; 
  
  return $sidebarTopics;
     
 }
 
 public function showRecentPosts($status, $position, $limit)
 {
  
  $sql = "SELECT
             ID, media_id, post_author,
             post_date, post_modified, post_title, post_slug,
             post_content, post_status, post_type
  		FROM
            tbl_posts
  		WHERE
            post_status = :post_status AND post_type = 'blog'
      ORDER BY ID DESC LIMIT :position, :limit";
      
  try {
  
  $this->setSQL($sql);
  
  $recentPosts = $this->findAll([':post_status' => $status, ':position' => $position, ':limit' => $limit]);
  
  return (empty($recentPosts)) ?: $recentPosts;
  
  } catch (DbException $e) {
      
    $this->closeConnection();
    $this->setError(LogError::newMessage($e));
    $this->setError(LogError::customErrorMessage());
    
  }
  
 }
  
}