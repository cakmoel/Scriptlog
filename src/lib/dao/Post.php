<?php
/**
 * Post class extends Dao
 *
 * @package   SCRIPTLOG/LIB/DAO/Post
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class Post extends Dao
{
 
protected $linkPosts;

public function __construct()
{
  parent::__construct();	
}

/**
 * Find posts
 * 
 * @param integer $position
 * @param integer $limit
 * @param string $orderBy
 * @param string $author
 * @return boolean|array|object
 * 
 */
public function findPosts($orderBy = 'ID', $author = null)
{
    if (!is_null($author)) {
        
        $sql = "SELECT p.ID, 
                p.media_id, 
                p.post_author,
                p.post_date, 
                p.post_modified, 
                p.post_title, 
                p.post_slug,
                p.post_content, 
                p.post_status, 
                p.post_type, 
                u.user_login
  			FROM tbl_posts AS p
  			INNER JOIN tbl_users AS u ON p.post_author = u.ID
  			WHERE p.post_author = :author
  			AND p.post_type = 'blog'
  			ORDER BY p.{$orderBy} DESC";
          
        $data = array(':author' => $author);

    } else {
        
        $sql = "SELECT p.ID, 
                p.media_id, 
                p.post_author,
                p.post_date, 
                p.post_modified, 
                p.post_title,
                p.post_slug, 
                p.post_content, 
                p.post_status, 
                p.post_type, 
                u.user_login
  		    FROM
                 tbl_posts AS p

  		    INNER JOIN

                 tbl_users AS u ON p.post_author = u.ID

  		    WHERE
                 p.post_type = 'blog'
  			ORDER BY p.{$orderBy} DESC";
          
        $data = array();

    }
    
    $this->setSQL($sql);

    $posts = $this->findAll($data);
    
    if (empty($posts)) return false;
    
    return $posts;
    
}

/**
 * Find single value post
 * 
 * @param integer $postId
 * @param object $sanitize
 * @param string $author
 * @return boolean|array|object
 */
public function findPost($id, $sanitize, $author = null)
{
    
   $idsanitized = $this->filteringId($sanitize, $id, 'sql');
    
   if (!empty($author)) {
        
        $sql = "SELECT ID, 
                  media_id, 
                  post_author,
  	  		      post_date, 
                  post_modified, 
                  post_title,
  	  		      post_slug, 
                  post_content, 
                  post_summary, 
                  post_keyword, 
                  post_status,
  	  		      post_type, 
                  comment_status
  	  		  FROM tbl_posts
  	  		  WHERE ID = :ID AND post_author = :author
  			  AND post_type = 'blog'";
        
        $data = array(':ID' => $idsanitized, ':author' => $author);
        
   } else {
        
       $sql = "SELECT ID, 
              media_id, 
              post_author,
  	  		  post_date, 
              post_modified, 
              post_title,
  	  		  post_slug, 
              post_content, 
              post_summary, 
              post_keyword, 
              post_status,
  	  		  post_type, 
              comment_status
  	  		  FROM tbl_posts
  	  		  WHERE ID = :ID AND post_type = 'blog'";
        
       $data = array(':ID' => $idsanitized);
        
  }
    
  $this->setSQL($sql);
  
  $postDetail = $this->findRow($data);

  if (empty($postDetail)) return false;
  
  return $postDetail;
   
}

/**
 * show detail post by id
 * 
 * @param integer $id
 * @param object $sanitize
 * @return boolean|array|object
 * 
 */
public function showPostById($id, $sanitize)
{
    $sql = "SELECT p.ID, p.media_id, p.post_author,
                p.post_date, p.post_modified, p.post_title,
                p.post_slug, p.post_content, p.post_summary, p.post_keyword, 
                p.post_status, p.post_type, p.comment_status, u.user_login
  		   FROM tbl_posts AS p
  		   INNER JOIN tbl_users AS u ON p.post_author = u.ID
  		   WHERE p.ID = :ID AND p.post_type = 'blog'";
    
    $sanitized_id = $this->filteringId($sanitize, $id, 'sql');
    
    $this->setSQL($sql);
    
    $readPost = $this->findRow([':ID' => $sanitized_id]);
    
    if (empty($readPost)) return false;
    
    return $readPost;
    
}

/**
 * Show Post by slug 
 * 
 * @param string $slug
 * @return mixed
 * 
 */
public function showPostBySlug($slug)
{
  
  $sql = "SELECT p.ID, p.media_id, p.post_author,
                 p.post_date, p.post_modified, p.post_title,
                 p.post_slug, p.post_content, p.post_summary, p.post_keyword, 
                 p.post_status, p.post_type, p.comment_status, u.user_login
          FROM tbl_posts AS p
          INNER JOIN tbl_users AS u ON p.post_author = u.ID
          WHERE p.post_slug = :slug AND p.post_type = 'blog'";

  $this->setSQL($sql);

  $readPost = $this->findRow([':slug' => $slug]);

  if (empty($readPost)) return false;

  return $readPost;
   
}

/**
 * show posts published
 * 
 * @param Paginator $perPage
 * @param object $sanitize
 * @return boolean|array[]|object[]|string[]
 */
public function showPostsPublished(Paginator $perPage, $sanitize)
{
    
    $pagination = null;
    
    $this->linkPosts = $perPage;
    
    $stmt = $this->dbc->dbQuery("SELECT ID FROM tbl_posts WHERE post_status = 'publish' AND post_type = 'blog'");
    
    $this->linkPosts->set_total($stmt -> rowCount());
    
    $sql = "SELECT p.ID, p.media_id, p.post_author,
                     p.post_date, p.post_modified, p.post_title,
                     p.post_slug, p.post_content, p.post_summary, p.post_keyword,
                     p.post_type, p.post_status, u.user_login
  			FROM tbl_posts AS p
  			INNER JOIN tbl_users AS u ON p.post_author = u.ID
  			WHERE p.post_type = 'blog' AND p.post_status = 'publish'
  			ORDER BY p.ID DESC " . $this->linkPosts->get_limit($sanitize);
    
    $this->setSQL($sql);
    
    $postsPublished = $this->findAll();
    
    $pagination = $this->linkPosts->page_links($sanitize);
    
    if (empty($postsPublished)) return false;

    return(['postsPublished' => $postsPublished, 'paginationLink' => $pagination]);
        
}

/**
 * Show related posts 
 * 
 * @param string $post_title
 * @return mixed
 * 
 */
public function showRelatedPosts($post_title)
{
  
  $sql = "SELECT ID, media_id, post_author, post_date, post_modified, 
                 post_title, post_slug, post_content, MATCH(post_title, post_content) 
                 AGAINST(?) AS score
          FROM tbl_posts WHERE MATCH(post_title, post_content) AGAINTS(?)
          ORDER BY score ASC LIMIT 3";
          
  $this->setSQL($sql);

  $relatedPosts = $this->findRow([$post_title]);

  if (empty($relatedPosts)) return false;

  return $relatedPosts;

}

/**
 * insert new post
 * 
 * @param array $bind
 * @param integer $topicId
 */
public function createPost($bind, $topicId)
{
  
 if (!empty($bind['media_id'])) {
  		
  	// insert into posts
   $stmt = $this->create("tbl_posts", [
       'media_id' => $bind['media_id'],
       'post_author' => $bind['post_author'],
       'post_date' => $bind['post_date'],
       'post_title' => $bind['post_title'],
       'post_slug' => $bind['post_slug'],
       'post_content' => $bind['post_content'],
       'post_summary' => $bind['post_summary'],
       'post_keyword' => $bind['post_keyword'],
       'post_status' => $bind['post_status'],
       'comment_status' => $bind['comment_status']
   ]);
     	 
 } else {
  			
  $stmt = $this->create("tbl_posts", [
      'post_author' => $bind['post_author'],
      'post_date' => $bind['post_date'],
      'post_title' => $bind['post_title'],
      'post_slug' => $bind['post_slug'],
      'post_content' => $bind['post_content'],
      'post_summary' => $bind['post_summary'],
      'post_keyword' => $bind['post_keyword'],
      'post_status' => $bind['post_status'],
      'comment_status' => $bind['comment_status']
  ]);
  		  
 }
  	
 $postId = $this->lastId();
 
 if ((is_array($topicId)) && (!empty($postId))) {
  			
  	foreach ($_POST['topic_id'] as $topicId) {
  	
  	$stmt2 = $this->create("tbl_post_topic", [
  	    'post_id' => $postId,
  	    'topic_id' => $topicId
  	]);
  			
   }
  			
 } else {
 
  $stmt2 = $this->create("tbl_post_topic", [
      'post_id' => $postId,
      'topic_id' => $topicId
  ]);
  
 }
 
}

/**
 * modify post
 * 
 * @param array $bind
 * @param integer $id
 * @param integer $topicId
 */
public function updatePost($sanitize, $bind, $ID, $topicId) 
{
        
 $cleanId = $this->filteringId($sanitize, $ID, 'sql');

 if (!empty($bind['media_id'])) {
  	  	
  	$stmt = $this->modify("tbl_posts", [
  	    'media_id' => $bind['media_id'],
  	    'post_author' => $bind['post_author'],
  	    'post_modified' => $bind['post_modified'],
  	    'post_title' => $bind['post_title'],
  	    'post_slug' => $bind['post_slug'],
  	    'post_content' => $bind['post_content'],
  	    'post_summary' => $bind['post_summary'],
  	    'post_keyword' => $bind['post_keyword'],
  	    'post_status' => $bind['post_status'],
  	    'comment_status' => $bind['comment_status']
  	], "ID = {$cleanId}");
  	 	
  } else {
  	 
      $stmt = $this->modify("tbl_posts", [
          'post_author' => $bind['post_author'],
          'post_modified' => $bind['post_modified'],
          'post_title' => $bind['post_title'],
          'post_slug' => $bind['post_slug'],
          'post_content' => $bind['post_content'],
          'post_summary' => $bind['post_summary'],
          'post_keyword' => $bind['post_keyword'],
          'post_status' => $bind['post_status'],
          'comment_status' => $bind['comment_status']
      ], "ID = {$cleanId}");
      
  }
  
  // query Id
  $this->setSQL("SELECT ID FROM tbl_posts WHERE ID = ?");
  $post_id = $this->findColumn([$cleanId]);
  
  // delete post_topic
  $stmt2 = $this->deleteRecord("tbl_post_topic", "ID = {$post_id['ID']}");
  	  
  if (is_array($topicId)) {
  	     
  	 foreach ($_POST['catID'] as $topicId) {
  	     
  	    $stmt3 = $this->create("tbl_post_topic", [
  	        'post_id' => $cleanId,
  	        'topic_id' => $topicId
  	    ]);
  	    
  	 }
  	     
  } else {
  	      
      $stmt3 = $this->create("tbl_post_topic", [
          'post_id' => $cleanId,
          'topic_id' => $topicId
      ]);
      
  }
  	  
}

/**
 * Delete post record
 * 
 * @param integer $id
 * @param object $sanitizing
 */
public function deletePost($id, $sanitize)
{ 
 $idsanitized = $this->filteringId($sanitize, $id, 'sql');
 $stmt = $this->deleteRecord("tbl_posts", "ID = {$idsanitized}"); 	  
}

/**
 * check post id
 * 
 * @param integer $id
 * @param object $sanitizing
 * @return numeric
 */
public function checkPostId($id, $sanitizing)
{
  $sql = "SELECT ID FROM tbl_posts WHERE ID = ? AND post_type = 'blog'";
  $idsanitized = $this->filteringId($sanitizing, $id, 'sql');
  $this->setSQL($sql);
  $stmt = $this->checkCountValue([$idsanitized]);
  return($stmt > 0); 		
}

/**
 * Drop down post status
 * set post status
 * 
 * @param string $selected
 * @return string
 * 
 */
public function dropDownPostStatus($selected = "")
{
  
  $name = 'post_status';
  // list position in array
  $posts_status = array('publish' => 'Publish', 'draft' => 'Draft');
 	
  if ($selected != '') {
     
    $selected = $selected;

  }
 	
  return dropdown($name, $posts_status, $selected);
 	
}

/**
 * Drop down Comment Status
 * set comment status
 * 
 * @param string $name
 * @return string
 * 
 */
public function dropDownCommentStatus($selected = "")
{
 	
    $name = 'comment_status';
 	// list position in array
 	$comment_status = array('open' => 'Open', 'close' => 'Close');
 	
 	if ($selected != '') {
 	    $selected = $selected;
 	}
 	
 	return dropdown($name, $comment_status, $selected);
 	
}

/**
 * Total posts records
 * 
 * @param array $data
 * @return numeric
 * 
 */
public function totalPostRecords($data = null)
{

  $sql = "SELECT ID FROM tbl_posts";
    
  $this->setSQL($sql);
    
  return $this->checkCountValue($data);
    
}
  
}