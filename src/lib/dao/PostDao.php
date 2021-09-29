<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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
 * findPosts
 * Retrieving all records from table posts
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
                p.post_headlines,
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
                p.post_headlines,
                p.post_type,
                u.user_login
  		  FROM tbl_posts AS p
  		  INNER JOIN tbl_users AS u ON p.post_author = u.ID
  		  WHERE p.post_type = 'blog'
  			ORDER BY :orderBy DESC";

        $data = array(':orderBy' => $orderBy);

    }

    $this->setSQL($sql);

    $posts = $this->findAll($data);

    return (empty($posts)) ?: $posts;

}

/**
 * findPost
 * Retrieving a single post records by it's Id 
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
                  post_headlines,
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
              post_headlines,
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
 * createPost
 * 
 * insert new post record
 *
 * @param array $bind
 * @param integer $topicId
 * 
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
       'post_headlines' => $bind['post_headlines'],
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
      'post_headlines' => $bind['post_headlines'],
      'comment_status' => $bind['comment_status']
   ]);

 }

 $postId = $this->lastId();

 if ((is_array($topicId)) && (!empty($postId))) {


  	foreach ($_POST['catID'] as $topicId) {

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
 * updatePost
 *
 * updating an existing post record
 * 
 * @param array $bind
 * @param integer $id
 * @param integer $topicId
 *
 */
public function updatePost($sanitize, $bind, $ID, $topicId)
{

 $cleanId = $this->filteringId($sanitize, $ID, 'sql');

try {

  $this->callTransaction(); // transaction

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
        'post_headlines' => $bind['post_headlines'],
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
          'post_headlines' => $bind['post_headlines'],
          'comment_status' => $bind['comment_status']
      ], "ID = {$cleanId}");

  }

  // query Id
  $this->setSQL("SELECT ID FROM tbl_posts WHERE ID = ?");
  $post_id = $this->findColumn([$cleanId]);

  // delete post_topic
  (!empty($post_id['ID'])) ? $this->deleteRecord("tbl_post_topic", "post_id = {$post_id['ID']}") : "";
  
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
public static function dropDownPostStatus($selected = "")
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
public static function dropDownCommentStatus($selected = "")
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
public function totalPostRecords($data = array())
{

  if (!empty($data)) {

    $sql = "SELECT ID FROM tbl_posts WHERE post_author = ? AND post_type = 'blog'";

  } else {

    $sql = "SELECT ID FROM tbl_posts WHERE post_type = 'blog'";

  }

  $this->setSQL($sql);

  return (empty($data)) ? $this->checkCountValue([]) : $this->checkCountValue($data);

}

}