<?php  
/**
 * Page class extends Dao
 * 
 * 
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class PageDao extends Dao
{

public function __construct()
{
  parent::__construct();
}

/**
 * Find pages
 * 
 * @param integer $position
 * @param integer $limit
 * @param string $type
 * @param string $orderBy
 * @return boolean|array|object
 */
public function findPages($type, $orderBy = 'ID')
{
   
   $sql = "SELECT ID, post_author, post_date, post_modified,
  		  post_title, post_type
  		  FROM tbl_posts WHERE post_type = :type
  		  ORDER BY :orderBy DESC";
    
    $this->setSQL($sql);
    
    $pages = $this->findAll([':type' => $type, ':orderBy' => $orderBy]);

    return (empty($pages)) ?: $pages;
    
}

/**
 * Find page by id
 * 
 * @param integer $pageId
 * @param string $post_type
 * @param object $sanitizing
 * @return boolean|array|object
 */
public function findPageById($pageId, $post_type, $sanitize)
{
    $sql = "SELECT ID, post_image, post_author,
  	  	      post_date, post_modified, post_title,
  	  	      post_slug, post_content, post_status,
  	  	      post_type, comment_status
  	  	   FROM tbl_posts
  	  	   WHERE ID = ? AND post_type = ? ";
    
    $id_sanitized = $this -> filteringId($sanitize, $pageId, 'sql');
    
    $this->setSQL($sql);
    
    $pageById = $this->findRow([$id_sanitized, $post_type]);
    
    return (empty($pageById)) ?: $pageById;
    
}

/**
 * Find page by slug title
 * 
 * @param string $slug
 * @return boolean|array|object
 * 
 */
public function findPageBySlug($slug, $sanitize)
{
    $sql = "SELECT
              tbl_posts.ID, tbl_posts.post_image, 
			  tbl_posts.post_author, tbl_posts.post_date, 
			  tbl_posts.post_modified, tbl_posts.post_title,
  	  	      tbl_posts.post_slug, tbl_posts.post_content,  
			  tbl_posts.post_status, tbl_posts.post_type, 
			  tbl_posts.comment_status, tbl_users.user_login
  	  	   FROM
               tbl_posts, tbl_users
  	  	   WHERE
              tbl_posts.post_slug = :slug
              AND tbl_posts.post_status = 'publish'
              AND tbl_posts.post_type = 'page' ";
    
	$this->setSQL($sql);
	
	$slug_sanitized = $this->filteringId($sanitize, $slug, 'xss');

    $pageBySlug = $this->findRow([':slug' => $slug_sanitized]);
    
    return (empty($pageBySlug)) ?: $pageBySlug;
    
}

/**
 * Insert new page
 * 
 * @param array $bind
 */
public function createPage($bind)
{
 
 if (!empty($bind['post_image'])) {
 
 	$this->create("tbl_posts", [
 	    'post_image' => $bind['post_image'],
 	    'post_author' => $bind['post_author'],
 	    'post_date' => $bind['post_date'],
 	    'post_content' => $bind['post_content'],
 	    'post_status' => $bind['post_status'],
 	    'post_type' => $bind['post_type'],
 	    'comment_status' => $bind['comment_status']
 	]);
 	
 } else {
 	
 	$this->create("tbl_posts", [
 	    'post_author' => $bind['post_author'],
 	    'post_date' => $bind['post_date'],
 	    'post_content' => $bind['post_content'],
 	    'post_status' => $bind['post_status'],
 	    'post_type' => $bind['post_type'],
 	    'comment_status' => $bind['comment_status']
 	]);
 	
 }
 
}

/**
 * Update page
 * 
 * @param array $bind
 * @param integer $id
 */
public function updatePage($sanitize, $bind, $ID)
{
 
 $cleanId = $this->filteringId($sanitize, $ID, 'sql');

 if (empty($bind['post_image'])) {
 
 	$this->modify("tbl_posts", [
 	    'post_modified' => $bind['post_modified'],
 	    'post_title' => $bind['post_title'],
 	    'post_slug' => $bind['post_slug'],
 	    'post_status' => $bind['post_status'],
 	    'comment_status' => $bind['comment_status']
 	], "ID = {$cleanId}"." AND `post_type` = {$bind['post_type']}");
 	
 } else {
 	
 	$this->modify("tbl_posts", [
 	    'post_image' => $bind['post_image'],
 	    'post_modified' => $bind['post_modified'],
 	    'post_title' => $bind['post_title'],
 	    'post_slug' => $bind['post_slug'],
 	    'post_status' => $bind['post_status'],
 	    'comment_status' => $bind['comment_status']
 	    ], "ID = {$cleanId}"." AND `post_type` = {$bind['post_type']}");
 	
 }
  	
}

/**
 * Delete page
 * 
 * @param integer $id
 * @param object $sanitizing
 * @param string $type
 */
public function deletePage($ID, $sanitize, $type)
{
 $clean_id = $this->filteringId($sanitize, $ID, 'sql');
 $this->deleteRecord("tbl_posts", "ID = ".(int)$clean_id." AND post_type = "."{$type}");  
}

/**
 * Check page id
 * 
 * @param integer $id
 * @param object $sanitizing
 * @return numeric
 */
public function checkPageId($id, $sanitizing)
{
   $cleanId = $this->filteringId($sanitizing, $id, 'sql');
   $sql = "SELECT ID FROM tbl_posts WHERE ID = ?";
   $this->setSQL($sql);
   $stmt = $this->checkCountValue([$cleanId]);
   return($stmt > 0);
}
 
/**
 * Set post status
 * 
 * @param string $selected
 * @return string
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
 * Set comment status
 * 
 * @param string $selected
 * @return string
 */
public function dropDownCommentStatus($selected = '')
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
 * Total page records
 * 
 * @param array $data
 * @return numeric
 */
public function totalPageRecords($data = null)
{
   $sql = "SELECT ID FROM tbl_posts WHERE post_type = 'page'";
   $this->setSQL($sql);
   return $this->checkCountValue();
}

}