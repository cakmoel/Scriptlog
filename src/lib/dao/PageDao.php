<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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

private $selected;

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
public function findPages($type, $orderBy = 'ID', $author = null)
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
				p.post_summary,
				p.post_keyword,
                p.post_status,
                p.post_sticky,
                p.post_type,
                u.user_login
  			FROM tbl_posts AS p
  			INNER JOIN tbl_users AS u ON p.post_author = u.ID
  			WHERE p.post_author = ?
  			AND p.post_type = ?
  			ORDER BY '$orderBy' DESC";

        $data = array($author, $type);

    } else {

        $sql = "SELECT p.ID,
                p.media_id,
                p.post_author,
                p.post_date,
                p.post_modified,
                p.post_title,
                p.post_slug,
                p.post_content,
				p.post_summary,
				p.post_keyword,
                p.post_status,
                p.post_sticky,
                p.post_type,
                u.user_login
  		  FROM tbl_posts AS p
  		  INNER JOIN tbl_users AS u ON p.post_author = u.ID
  		  WHERE p.post_type = ?
  		  ORDER BY '$orderBy' DESC";

        $data = array($type);

    }

    $this->setSQL($sql);
    
    $pages = $this->findAll($data);

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
public function findPageById($pageId, $sanitize)
{
	
	$idsanitized = $this->filteringId($sanitize, $pageId, 'sql');

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
				   post_sticky, 
				   post_type
  	  	   FROM tbl_posts
  	  	   WHERE ID = ? AND post_type = 'page' ";
     
    $this->setSQL($sql);
    
    $pageById = $this->findRow([$idsanitized]);
    
    return (empty($pageById)) ?: $pageById;
    
}

/**
 * createPage()
 * 
 * Insert new page record
 * 
 * @param array $bind
 * 
 */
public function createPage($bind)
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
		'post_status' => $bind['post_status'],
		'post_sticky' => $bind['post_sticky'],
 	    'post_type' => $bind['post_type'],
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
		'post_status' => $bind['post_status'],
		'post_sticky' => $bind['post_sticky'], 
 	    'post_type' => $bind['post_type'],
 	    'comment_status' => $bind['comment_status']
 	]);
 	
 }
 
}

/**
 * UpdatePage
 * 
 * Updating an existing page record
 * 
 * @param array $bind
 * @param integer $id
 * 
 */
public function updatePage($sanitize, $bind, $ID)
{
 
 $cleanId = $this->filteringId($sanitize, $ID, 'sql');

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
		'post_status' => $bind['post_status'],
		'post_sticky' => $bind['post_sticky'], 
		'post_type' => $bind['post_type']
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
		'post_status' => $bind['post_status'],
		'post_sticky' => $bind['post_sticky'],
		'post_type' => $bind['post_type']
 	    ], "ID = {$cleanId}");
 	
 }
  	
}

/**
 * deletePage
 * 
 * Deleting an existing record based on it's ID
 * 
 * @param integer $id
 * @param object $sanitizing
 * @param string $type
 */
public function deletePage($ID, $sanitize, $type)
{
 $clean_id = $this->filteringId($sanitize, $ID, 'sql');
 $this->deleteRecord("tbl_posts", "ID = ".(int)$clean_id." AND `post_type` = "."{$type}");  
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
   return $stmt > 0;
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
	
	 $this->selected = $selected;
 }
    
 return dropdown($name, $posts_status, $this->selected);
    
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
    
 $comment_status = array('open' => 'Open', 'close' => 'Close');
    
 if ($selected != '') {
    $this->selected = $selected;
 }
    
 return dropdown($name, $comment_status, $this->selected);
    
}

/**
 * Total page records
 * 
 * @param array $data
 * @return numeric
 */
public function totalPageRecords($data = array())
{
   $sql = "SELECT ID FROM tbl_posts WHERE post_type = 'page'";
   $this->setSQL($sql);
   return (empty($data)) ? $this->checkCountValue([]) : $this->checkCountValue($data);
}

}