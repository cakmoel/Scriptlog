<?php
/**
 * Post class extends Dao
 *
 * 
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class PostDao extends Dao
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
                p.post_tags,
                p.post_status, 
                p.post_type, 
                u.user_login
  			FROM tbl_posts AS p
  			INNER JOIN tbl_users AS u ON p.post_author = u.ID
  			WHERE p.post_author = :author
  			AND p.post_type = 'blog'
  			ORDER BY :orderBy DESC";
          
        $data = array(':author' => $author, ':orderBy' => $orderBy);

    } else {
        
        $sql = "SELECT p.ID, 
                p.media_id, 
                p.post_author,
                p.post_date, 
                p.post_modified, 
                p.post_title,
                p.post_slug, 
                p.post_content, 
                p.post_tags,
                p.post_status, 
                p.post_type, 
                u.user_login
  		    FROM
                 tbl_posts AS p

  		    INNER JOIN

                 tbl_users AS u ON p.post_author = u.ID

  		    WHERE
                 p.post_type = 'blog'

  			ORDER BY :orderBy DESC";
          
        $data = array(':orderBy' => $orderBy);

    }
    
    $this->setSQL($sql);

    $posts = $this->findAll($data);
    
    return (empty($posts)) ?: $posts;
    
}

/**
 * Find single value post
 * 
 * @param integer $postId
 * @param object $sanitize
 * @param string $author
 * @return boolean|array|object
 * 
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
                  post_tags,
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
              post_tags,
              post_status,
  	  		    post_type, 
              comment_status
  	  		  FROM tbl_posts
  	  		  WHERE ID = :ID AND post_type = 'blog'";
        
       $data = array(':ID' => $idsanitized);
        
  }
    
  $this->setSQL($sql);
  
  $postDetail = $this->findRow($data);

  return (empty($postDetail)) ?: $postDetail;
   
}

/**
 * Retrieve posts records for sharing post
 * on post feeds
 *
 * @param integer $limit
 * @return void
 */
public function showPostFeeds($limit = 5)
{
  $sql =  "SELECT p.ID, p.media_id, p.post_author,
                  p.post_date, p.post_modified, p.post_title,
                  p.post_slug, p.post_content, p.post_tags, p.post_type,
                  p.post_status, u.user_login
            FROM tbl_posts AS p
            INNER JOIN tbl_users AS u ON p.post_author = u.ID
            WHERE p.post_type = 'blog' AND p.post_status = 'publish' 
            ORDER BY p.ID DESC LIMIT :limit";

  $data = array(':limit' => $limit);

  $this->setSQL($sql);

  $feeds = $this->findAll($data);
    
  return (empty($feeds)) ?: $feeds;

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
                p.post_slug, p.post_content, p.post_summary, 
                p.post_keyword, p.post_tags,
                p.post_status, p.post_type, p.comment_status, u.user_login
  		   FROM tbl_posts AS p
  		   INNER JOIN tbl_users AS u ON p.post_author = u.ID
  		   WHERE p.ID = :ID AND p.post_type = 'blog'";
    
    $sanitized_id = $this->filteringId($sanitize, $id, 'sql');
    
    $this->setSQL($sql);
    
    $postById = $this->findRow([':ID' => $sanitized_id]);
    
    return (empty($postById)) ?: $postById;
    
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
                 p.post_slug, p.post_content, p.post_summary, 
                 p.post_keyword, p.post_tags,
                 p.post_status, p.post_type, p.comment_status, u.user_login
          FROM tbl_posts AS p
          INNER JOIN tbl_users AS u ON p.post_author = u.ID
          WHERE p.post_slug = :slug AND p.post_type = 'blog'";

  $this->setSQL($sql);

  $postBySlug = $this->findRow([':slug' => $slug]);

  return (empty($postBySlug)) ?: $postBySlug;
   
}

/**
 * show posts published
 * 
 * @param Paginator $perPage
 * @param object $sanitize
 * @return boolean|array[]|object[]|string[]
 * 
 */
public function showPostsPublished(Paginator $perPage, $sanitize)
{
    
    $pagination = null;
    
    $this->linkPosts = $perPage;
    
    $stmt = $this->dbc->dbQuery("SELECT ID FROM tbl_posts WHERE post_status = 'publish' AND post_type = 'blog'");
    
    $this->linkPosts->set_total($stmt -> rowCount());
    
    $sql = "SELECT p.ID, p.media_id, p.post_author,
                     p.post_date, p.post_modified, p.post_title,
                     p.post_slug, p.post_content, p.post_summary, 
                     p.post_keyword, p.post_tags,
                     p.post_type, p.post_status, u.user_login
  			FROM tbl_posts AS p
  			INNER JOIN tbl_users AS u ON p.post_author = u.ID
  			WHERE p.post_type = 'blog' AND p.post_status = 'publish'
  			ORDER BY p.ID DESC " . $this->linkPosts->get_limit($sanitize);
    
    $this->setSQL($sql);
    
    $postsPublished = $this->findAll();
    
    $pagination = $this->linkPosts->page_links($sanitize);
    
    return (empty($postsPublished)) ?: ['postsPublished' => $postsPublished, 'paginationLink' => $pagination];
        
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
                 post_title, post_slug, post_content, post_tags, MATCH(post_title, post_content, post_tags) 
                 AGAINST(?) AS score
          FROM tbl_posts WHERE MATCH(post_title, post_content) AGAINTS(?)
          ORDER BY score ASC LIMIT 3";
          
  $this->setSQL($sql);

  $relatedPosts = $this->findRow([$post_title]);

  return (empty($relatedPosts)) ?: $relatedPosts;

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
  		
   $this->create("tbl_posts", [
       'media_id' => $bind['media_id'],
       'post_author' => $bind['post_author'],
       'post_date' => $bind['post_date'],
       'post_title' => $bind['post_title'],
       'post_slug' => $bind['post_slug'],
       'post_content' => $bind['post_content'],
       'post_summary' => $bind['post_summary'],
       'post_keyword' => $bind['post_keyword'],
       'post_tags' => $bind['post_tags'],
       'post_status' => $bind['post_status'],
       'comment_status' => $bind['comment_status']
   ]);
     	 
 } else {
  			
   $this->create("tbl_posts", [
      'post_author' => $bind['post_author'],
      'post_date' => $bind['post_date'],
      'post_title' => $bind['post_title'],
      'post_slug' => $bind['post_slug'],
      'post_content' => $bind['post_content'],
      'post_summary' => $bind['post_summary'],
      'post_keyword' => $bind['post_keyword'],
      'post_tags' => $bind['post_tags'],
      'post_status' => $bind['post_status'],
      'comment_status' => $bind['comment_status']
   ]);
  		  
 }
  	
 $postId = $this->lastId();
 
 if ((is_array($topicId)) && (!empty($postId))) {
  			
  	foreach ($_POST['topic_id'] as $topicId) {
  	
  	  $this->create("tbl_post_topic", [
  	    'post_id' => $postId,
  	    'topic_id' => $topicId]);
  			
   }
  			
 } else {
 
    $this->create("tbl_post_topic", [
      'post_id' => $postId,
      'topic_id' => $topicId]);
  
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

try {
  
  $this->callTransaction();

  if (!empty($bind['media_id'])) {
  	  	
  	$this->modify("tbl_posts", [
  	    'media_id' => $bind['media_id'],
  	    'post_author' => $bind['post_author'],
  	    'post_modified' => $bind['post_modified'],
  	    'post_title' => $bind['post_title'],
  	    'post_slug' => $bind['post_slug'],
  	    'post_content' => $bind['post_content'],
  	    'post_summary' => $bind['post_summary'],
        'post_keyword' => $bind['post_keyword'],
        'post_tags' => $bind['post_tags'],
  	    'post_status' => $bind['post_status'],
  	    'comment_status' => $bind['comment_status']
  	], "ID = {$cleanId}");
  	 	
  } else {
  	 
      $this->modify("tbl_posts", [
          'post_author' => $bind['post_author'],
          'post_modified' => $bind['post_modified'],
          'post_title' => $bind['post_title'],
          'post_slug' => $bind['post_slug'],
          'post_content' => $bind['post_content'],
          'post_summary' => $bind['post_summary'],
          'post_keyword' => $bind['post_keyword'],
          'post_tags' => $bind['post_tags'],
          'post_status' => $bind['post_status'],
          'comment_status' => $bind['comment_status']
      ], "ID = {$cleanId}");
      
  }
  
  // query Id
  $this->setSQL("SELECT ID FROM tbl_posts WHERE ID = ?");
  $post_id = $this->findColumn([$cleanId]);
  
  // delete post_topic
  $this->deleteRecord("tbl_post_topic", "post_id = '{$post_id['ID']}'");
  	  
  if (is_array($topicId)) {
  	     
  	 foreach ($_POST['catID'] as $topicId) {
  	     
  	    $this->create("tbl_post_topic", [
  	        'post_id' => $cleanId,
  	        'topic_id' => $topicId
  	    ]);
  	    
  	 }
  	     
  } else {
  	      
      $this->create("tbl_post_topic", [
          'post_id' => $cleanId,
          'topic_id' => $topicId
      ]);
      
  }
  
  $this->callCommit();
  
} catch (DbException $e) {
  
   $this->callRollBack();
   $this->error = LogError::newMessage($e);
   $this->error = LogError::customErrorMessage('admin');

}
  	  
}

/**
 * Delete post record
 * 
 * @param integer $id
 * @param object $sanitizing
 * 
 */
public function deletePost($id, $sanitize)
{ 
 $clean_id = $this->filteringId($sanitize, $id, 'sql');
 $this->deleteRecord("tbl_posts", "ID = ".(int)$clean_id); 	  
}

/**
 * check post id
 * 
 * @param integer $id
 * @param object $sanitizing
 * @return numeric
 * 
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
 	
 	$comment_status = array('open' => 'Open', 'closed' => 'Closed');
 	
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

  if (!empty($data)) {

     $sql = "SELECT ID FROM tbl_posts WHERE post_author = ?";

  } else {

     $sql = "SELECT ID FROM tbl_posts";

  }
    
  $this->setSQL($sql);
    
  return $this->checkCountValue($data);
    
}
  
}